<?php

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with 'api/v1'.
| These routes use the root namespace 'App\Http\Controllers\Api\V1'.
|
 */
use App\Base\Constants\Auth\Role;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\OpenPixController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Payment\Stripe\StripeController;
use App\Http\Controllers\Api\V1\Payment\Orange\OrangeController;
use App\Http\Controllers\Api\V1\Payment\Mercadopago\MercadoPagoController;
use App\Http\Controllers\Api\V1\Payment\Bankily\BankilyController;

/*
 * These routes are prefixed with 'api/v1/payment'.
 * These routes use the root namespace 'App\Http\Controllers\Api\V1\Payment'.
 * These routes use the middleware group 'auth'.
 */
/*
|--------------------------------------------------------------------------
| Authenticated Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('payment')
    ->middleware(['auth:sanctum', 'throttle:120,1'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Cards
        |--------------------------------------------------------------------------
        */
        Route::prefix('cards')->group(function () {
            Route::get('list', [PaymentController::class, 'listCards']);
            Route::post('make-default', [PaymentController::class, 'makeDefaultCard']);
            Route::post('delete/{card}', [PaymentController::class, 'deleteCard']);
        });

        /*
        |--------------------------------------------------------------------------
        | Wallet
        |--------------------------------------------------------------------------
        */
        Route::prefix('wallet')->group(function () {
            Route::get('history', [PaymentController::class, 'walletHistory'])->middleware('throttle:500,1');
            Route::get('withdrawal-requests', [PaymentController::class, 'withDrawalRequests']);
            Route::post('request-for-withdrawal', [PaymentController::class, 'requestForWithdrawal']);
            Route::post('transfer-money-from-wallet', [PaymentController::class, 'transferMoneyFromWallet']);
            Route::post('convert-point-to-wallet', [PaymentController::class, 'transferCreditFromPoints']);
        });

        /*
        |--------------------------------------------------------------------------
        | Stripe
        |--------------------------------------------------------------------------
        */
        Route::prefix('stripe')->group(function () {
            Route::post('create-setup-intent', [StripeController::class, 'createStripeIntent']);
            Route::post('save-card', [StripeController::class, 'saveCard']);
            Route::post('add-money-to-wallet', [StripeController::class, 'addMoneyToWalletByStripe']);
        });

        /*
        |--------------------------------------------------------------------------
        | Orange
        |--------------------------------------------------------------------------
        */
        Route::prefix('orange')->group(function () {
            Route::get('/', [OrangeController::class, 'makePayment']);
        });

        /*
        |--------------------------------------------------------------------------
        | MercadoPago
        |--------------------------------------------------------------------------
        */
        Route::prefix('mercadopago')->group(function () {
            Route::get('/', [MercadoPagoController::class, 'makePayment']);
        });

        /*
        |--------------------------------------------------------------------------
        | Bankily
        |--------------------------------------------------------------------------
        */
        Route::prefix('bankily')->group(function () {
            Route::get('/authenticate', [BankilyController::class, 'authenticate']);
            Route::get('/refresh', [BankilyController::class, 'refresh']);
            Route::get('/payment', [BankilyController::class, 'payment']);
            Route::get('/status', [BankilyController::class, 'status']);
        });
    });


/*
|--------------------------------------------------------------------------
| Public + Semi‑Public Payment Routes
|--------------------------------------------------------------------------
*/

Route::prefix('payment')->group(function () {

    Route::middleware(['auth:sanctum', 'throttle:30,1'])
        ->get('gateway', [PaymentController::class, 'paymentGateways']);

    Route::get('gateway-for-ride', [PaymentController::class, 'paymentGatewaysForRide']);

    Route::prefix('stripe')->group(function () {
        Route::post('listen-webhooks', [StripeController::class, 'listenWebHooks']);
    });
});


/*
|--------------------------------------------------------------------------
| OpenPix Webhook
|--------------------------------------------------------------------------
*/

Route::any('open-pix/callback', [OpenPixController::class, 'webhook'])
    ->name('mercadopago.pix.webhook');
