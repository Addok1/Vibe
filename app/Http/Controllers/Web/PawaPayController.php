<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\PaymentGatewayController;
use App\Models\Payment\PaymentRequest;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use App\Services\Payments\PawaPayService;
use Illuminate\Http\Request as ValidatorRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Database;

class PawaPayController extends PaymentGatewayController
{
    protected Database $database;
    protected PawaPayService $pawaPay;

    public function __construct(Database $database, PawaPayService $pawaPay)
    {
        $this->database = $database;
        $this->pawaPay = $pawaPay;
    }

    public function pawapay(ValidatorRequest $request)
    {
        $payment = $this->storePayment($request->all());

        return view('pawapay.pawapay', compact('payment'));
    }

    public function initiate(ValidatorRequest $request)
    {
        $transactionId = (string) $request->input('transaction_id');

        $payment = $this->getPaymentDetail($transactionId);
        if (!$payment) {
            return view('failure', ['failure']);
        }

        if ($payment->status === 'S') {
            $request_id = $payment->request_id;
            $requestDetail = $request_id ? RequestModel::where('id', $request_id)->first() : null;
            $web_booking_value = $requestDetail->web_booking ?? 0;

            return view('success', ['success'], compact('web_booking_value', 'request_id'));
        }

        if ($payment->status === 'F') {
            return view('failure', ['failure']);
        }

        $user = User::find($payment->user_id);
        if (!$user) {
            return view('failure', ['failure']);
        }

        $countryCode = strtoupper((string) ($user->countryDetail->code ?? ''));
        $mobileNumber = $this->normalizeMsisdn($user);
        $correspondent = trim((string) get_payment_settings('pawapay_correspondent'));
        $depositId = $payment->pawapay_deposit_id ?: (string) Str::uuid();

        if (!$payment->pawapay_deposit_id) {
            $payment->update(['pawapay_deposit_id' => $depositId]);
        }

        if ($correspondent === '' && $mobileNumber !== '') {
            try {
                $predictionResponse = $this->pawaPay->predictCorrespondent($mobileNumber);
                $prediction = $predictionResponse->json();

                if ($predictionResponse->successful()) {
                    $correspondent = (string) ($prediction['correspondent'] ?? '');
                    $countryCode = strtoupper((string) ($prediction['country'] ?? $countryCode));
                    $mobileNumber = (string) ($prediction['msisdn'] ?? $mobileNumber);
                } else {
                    Log::error('PawaPay correspondent prediction failed', [
                        'transaction_id' => $transactionId,
                        'status' => $predictionResponse->status(),
                        'body' => $prediction,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('PawaPay correspondent prediction exception', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($countryCode === '' || strlen($countryCode) !== 3) {
            $countryCode = $this->countryFromCorrespondent($correspondent);
        }

        if ($correspondent === '' || $mobileNumber === '') {
            Log::error('PawaPay initiate missing required data', [
                'transaction_id' => $transactionId,
                'message' => 'Set a valid pawapay_correspondent in Payment Gateway settings and use a supported PawaPay mobile-money country/mobile number.',
                'country' => $countryCode,
                'correspondent' => $correspondent,
                'has_mobile_number' => $mobileNumber !== '',
            ]);
            return view('failure', ['failure']);
        }

        $payload = array_filter([
            'depositId' => $depositId,
            'amount' => $this->formatAmount($payment->amount),
            'currency' => strtoupper((string) $payment->currency),
            'correspondent' => $correspondent,
            'payer' => [
                'type' => 'MSISDN',
                'address' => [
                    'value' => $mobileNumber,
                ],
            ],
            'customerTimestamp' => now()->utc()->toIso8601String(),
            'statementDescription' => 'Wallet Payment',
            'country' => strlen($countryCode) === 3 ? $countryCode : null,
            'metadata' => [
                [
                    'fieldName' => 'payment_id',
                    'fieldValue' => $transactionId,
                ],
                [
                    'fieldName' => 'user_id',
                    'fieldValue' => (string) $payment->user_id,
                ],
            ],
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $response = $this->pawaPay->createDeposit($payload);
        } catch (\Throwable $e) {
            Log::error('PawaPay initiate exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return view('failure', ['failure']);
        }

        if (!$response->successful()) {
            Log::error('PawaPay initiate failed', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'payload' => $payload,
                'body' => $response->json(),
            ]);
            return view('failure', ['failure']);
        }

        $body = $response->json();
        $deposit = (is_array($body) && isset($body[0]) && is_array($body[0])) ? $body[0] : $body;
        $status = strtoupper((string) ($deposit['status'] ?? ''));

        if (in_array($status, ['REJECTED', 'FAILED'], true)) {
            PaymentRequest::where('id', $transactionId)->update(['status' => 'F']);
            Log::error('PawaPay initiate rejected', [
                'transaction_id' => $transactionId,
                'deposit_id' => $depositId,
                'body' => $body,
            ]);
            return view('failure', ['failure']);
        }

        return view('pending', [
            'pending',
            'transaction_id' => $transactionId,
            'status_url' => route('pawapay.status', ['transaction_id' => $transactionId]),
        ]);
    }

    public function status(ValidatorRequest $request, string $transaction_id)
    {
        $payment = PaymentRequest::where('id', $transaction_id)->first();

        if (!$payment) {
            return response()->json([
                'status' => 'failed',
                'redirect_url' => route('failure'),
            ], 404);
        }

        if ($payment->status === 'S') {
            return response()->json([
                'status' => 'success',
                'redirect_url' => url('success'),
            ]);
        }

        if ($payment->status === 'F') {
            return response()->json([
                'status' => 'failed',
                'redirect_url' => route('failure'),
            ]);
        }

        $remoteStatus = null;
        $remoteError = null;
        $remoteDebug = null;

        if ($payment->pawapay_deposit_id) {
            try {
                $response = $this->pawaPay->getDeposit($payment->pawapay_deposit_id);
                $body = $response->json();
                $rawBody = (string) $response->body();
                $contentType = (string) $response->header('Content-Type', '');
                $deposit = (is_array($body) && isset($body[0]) && is_array($body[0])) ? $body[0] : $body;
                $remoteDebug = [
                    'http_status' => $response->status(),
                    'content_type' => $contentType,
                    'json' => $body,
                    'raw_body' => Str::limit($rawBody, 1500),
                ];

                if ($response->successful()) {
                    $status = strtoupper((string) ($deposit['status'] ?? ''));
                    $remoteStatus = $status;

                    if ($remoteStatus === '') {
                        $remoteError = 'PawaPay response missing status field';
                        Log::warning('PawaPay deposit response missing status', [
                            'transaction_id' => $payment->id,
                            'deposit_id' => $payment->pawapay_deposit_id,
                            'body' => $body,
                            'content_type' => $contentType,
                            'raw_body' => Str::limit($rawBody, 4000),
                        ]);
                    }

                    $finalStatus = $this->applyPawaPayStatus($payment, $status);

                    if ($finalStatus === 'success') {
                        return response()->json([
                            'status' => 'success',
                            'redirect_url' => url('success'),
                            'pawapay_status' => $remoteStatus,
                        ]);
                    }

                    if ($finalStatus === 'failed') {
                        return response()->json([
                            'status' => 'failed',
                            'redirect_url' => route('failure'),
                            'pawapay_status' => $remoteStatus,
                        ]);
                    }
                } else {
                    $remoteError = 'PawaPay status check failed (HTTP ' . $response->status() . ')';
                    Log::error('PawaPay status check failed', [
                        'transaction_id' => $payment->id,
                        'deposit_id' => $payment->pawapay_deposit_id,
                        'status' => $response->status(),
                        'body' => $body,
                    ]);
                }
            } catch (\Throwable $e) {
                $remoteError = 'PawaPay status check exception';
                Log::error('PawaPay status check exception', [
                    'transaction_id' => $payment->id,
                    'deposit_id' => $payment->pawapay_deposit_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 'pending',
            'pawapay_status' => $remoteStatus,
            'error' => $remoteError,
            'debug' => (config('app.debug') && $remoteError) ? $remoteDebug : null,
        ]);
    }

    public function webhook(ValidatorRequest $request)
    {
        $secret = (string) get_payment_settings('pawapay_webhook_secret');
        if ($secret !== '') {
            $provided = (string) $request->header('x-pawapay-signature', '');
            if ($provided !== '') {
                $expected = hash_hmac('sha256', (string) $request->getContent(), $secret);
                if (!hash_equals($expected, $provided)) {
                    return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
                }
            }
        }

        $depositId = (string) ($request->input('depositId') ?? $request->input('id') ?? '');
        $status = strtoupper((string) ($request->input('status') ?? $request->input('state') ?? ''));

        if ($depositId === '') {
            return response()->json(['success' => false, 'message' => 'Missing depositId'], 400);
        }

        $payment = PaymentRequest::where('pawapay_deposit_id', $depositId)->first();

        if (!$payment) {
            $paymentId = $this->metadataValue($request->input('metadata'), 'payment_id');
            $payment = $paymentId ? PaymentRequest::where('id', $paymentId)->first() : null;
        }

        if (!$payment) {
            $payment = PaymentRequest::where('id', $depositId)->first();
        }

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        if ($this->applyPawaPayStatus($payment, $status) === 'success') {
            return response()->json(['success' => true], 200);
        }

        if ($this->applyPawaPayStatus($payment, $status) === 'failed') {
            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => true, 'message' => 'Ignored'], 200);
    }

    private function formatAmount($amount): string
    {
        $formatted = number_format((float) $amount, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    // private function applyPawaPayStatus(PaymentRequest $payment, string $status): string
    // {
    //     if (in_array($status, ['COMPLETED', 'SUCCESS', 'SUCCESSFUL', 'APPROVED', 'PAID'], true)) {
    //         if ($payment->status !== 'S') {
    //             $this->payNow($payment->id, $this->database);
    //         }

    //         return 'success';
    //     }

    //     if (in_array($status, ['FAILED', 'REJECTED', 'CANCELLED', 'CANCELED', 'ERROR'], true)) {
    //         PaymentRequest::where('id', $payment->id)->update(['status' => 'F']);

    //         return 'failed';
    //     }

    //     return 'pending';
    // }

    private function applyPawaPayStatus(PaymentRequest $payment, string $status): string
{
    if (in_array($status, [
        'COMPLETED',
        'SUCCESS',
        'SUCCESSFUL',
        'APPROVED',
        'PAID'
    ], true)) {

        if ($payment->status !== 'S') {
            $this->payNow($payment->id, $this->database);
        }

        return 'success';
    }

    if (in_array($status, [
        'FAILED',
        'REJECTED',
        'CANCELLED',
        'CANCELED',
        'ERROR'
    ], true)) {

        PaymentRequest::where('id', $payment->id)
            ->update(['status' => 'F']);

        return 'failed';
    }

    // ADD THIS
    Log::info('PawaPay Pending Status', [
        'payment_id' => $payment->id,
        'status' => $status
    ]);

    return 'pending';
}

    private function metadataValue($metadata, string $fieldName): ?string
    {
        if (!is_array($metadata)) {
            return null;
        }

        if (isset($metadata[$fieldName])) {
            return (string) $metadata[$fieldName];
        }

        foreach ($metadata as $item) {
            if (
                is_array($item)
                && ($item['fieldName'] ?? null) === $fieldName
                && isset($item['fieldValue'])
            ) {
                return (string) $item['fieldValue'];
            }
        }

        return null;
    }

    private function normalizeMsisdn(User $user): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $user->getRawOriginal('mobile'));
        $dialCode = preg_replace('/\D+/', '', (string) ($user->countryDetail->dial_code ?? ''));

        if ($mobile === '') {
            return '';
        }

        $mobile = ltrim($mobile, '0');

        if ($dialCode !== '' && !str_starts_with($mobile, $dialCode)) {
            return $dialCode . $mobile;
        }

        return $mobile;
    }

    private function countryFromCorrespondent(string $correspondent): ?string
    {
        $parts = explode('_', $correspondent);
        $countryCode = strtoupper((string) end($parts));

        return strlen($countryCode) === 3 ? $countryCode : null;
    }
}
