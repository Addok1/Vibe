<?php

use App\Http\Controllers\Web\PayPalController;
use App\Http\Controllers\Web\StripeController;
use App\Http\Controllers\Web\MyFatooraController;
use App\Http\Controllers\Web\FlutterwaveController;
use App\Http\Controllers\Web\CashfreeController;
use App\Http\Controllers\Web\KhaltiController;
use App\Http\Controllers\Web\RazorPayController;
use App\Http\Controllers\Web\MercadopagoController;
use App\Http\Controllers\Web\OpenPixController;
use App\Http\Controllers\Web\CcavenueController;
use App\Http\Controllers\Web\EasypaisaController;
use App\Http\Controllers\Web\AirtelController;
use App\Http\Controllers\Web\PaystackController;
use App\Http\Controllers\Web\PayphoneController;
use App\Http\Controllers\Web\PaytechController;
use App\Http\Controllers\Web\FlexpaieController;
use App\Http\Controllers\Web\SslCommerzController;
use App\Http\Controllers\Web\PawaPayController;
use App\Http\Controllers\Web\PaymongoController;
use App\Http\Controllers\Web\FedaPayController;
use Illuminate\Support\Facades\Route;


//paymentGateways
//paypall
// paypal?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('paypal', [PayPalController::class , 'paypal'])->name('paypal');
Route::post('paypal/payment', [PayPalController::class , 'payment'])->name('paypal.payment');
Route::get('paypal/payment/success', [PayPalController::class , 'paymentSuccess'])->name('paypal.payment.success');
Route::get('paypal/payment/cancel', [PayPalController::class , 'paymentCancel'])->name('paypal.payment/cancel');
//stripe
// stripe?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('stripe', [StripeController::class , 'stripe'])->name('stripe');
Route::post('stripe-checkout', [StripeController::class , 'stripeCheckout'])->name('checkout.process');
Route::get('stripe-checkout-success', [StripeController::class , 'stripeCheckoutSuccess'])->name('checkout.success');
Route::get('stripe-checkout-error', [StripeController::class , 'stripeCheckoutError'])->name('checkout.failure');

// Airtel
// Route::get('airtel', [AirtelController::class, 'airtel'])->name('airtel');
// Route::post('airtel-checkout', [AirtelController::class, 'airtelCheckout'])->name('airtel.checkout.process');
// Route::get('airtel-checkout-success', [AirtelController::class, 'airtelCheckoutSuccess'])->name('airtel.checkout.success'); // reuse existing success
// Route::get('airtel-checkout-error', [AirtelController::class, 'airtelCheckoutError'])->name('airtel.checkout.failure');

//fluterwave
// flutterwave?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('flutterwave', [FlutterwaveController::class , 'flutterwave'])->name('flutterwave');
Route::get('flutterwave/payment/success', [FlutterwaveController::class , 'flutterwaveCheckout'])->name('flutterwave.success');

//cashfree   

Route::get('cashfree', [CashfreeController::class , 'create'])->name('cashfree');
Route::post('cashfree/payments/store', [CashfreeController::class , 'store'])->name('store');
Route::any('cashfree/payments/success', [CashfreeController::class , 'success'])->name('cashfree.success');

//paystack
// paystack?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('paystack', [PaystackController::class , 'paystack'])->name('paystack');
Route::get('paystack/payment/success', [PaystackController::class , 'paystackCheckout'])->name('paystack.checkout');
//khalti
Route::get('khalti', [KhaltiController::class , 'khalti'])->name('khalti');
Route::post('khalti/checkout', [KhaltiController::class , 'khaltiCheckoutsuccess'])->name('khalti.success');
//razorpay
Route::get('/razorpay', [RazorPayController::class , 'razorpay'])->name('razorpay');
Route::get('/payment-success', [RazorPayController::class , 'razorpay_success'])->name('razorpay.success');
//mercadopago
// mercadopago?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('mercadopago', [MercadopagoController::class , 'mercadepago'])->name('mercadopago');



Route::get('mercadopago/payment/success', [MercadopagoController::class , 'mercadopagoCheckout'])->name('mercadopago.success');

Route::any('/webhook/mercadopago', [MercadopagoController::class , 'mercadopagoWebhook'])->name('mercadopago.webhook');

// Open pix

Route::get('open-pix', [OpenPixController::class , 'openPix'])->name('openpix');

// Route::any('open-pix/callback', [OpenPixController::class, 'webhook']);    

//paytech
// paytech?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('paytech', [PaytechController::class , 'index'])->name('paytech');
Route::post('/paytech/initiate', [PaytechController::class , 'initiatePayTech'])->name('paytech.initiate');
Route::get('paytech/payment/success', [PaytechController::class , 'paytechCheckout'])->name('paytech.checkout');

// flexipay
// paytech?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('flexpaie', [FlexpaieController::class , 'flexpaie'])->name('flexpaie');
Route::post('flexpaie/pay', [FlexpaieController::class , 'flexpaieCheckout'])->name('flexpaie.pay');
Route::get('flexpaie/payment/success', [FlexpaieController::class , 'flexpaieCheckoutSuccess'])->name('flexpaie.checkout');

//ccavenue   Not completed
// ccavenue?amount=100&payment_for=wallet&currency=USD&user_id=2&payment_for=wallet&request_id
Route::get('ccavenue', [CcavenueController::class , 'index'])->name('ccavenue');
Route::post('ccavenue/checkout', [CcavenueController::class , 'ccavenueCheckout'])->name('ccavenue.checkout');
Route::get('ccavenue/payment/success', [CcavenueController::class , 'success'])->name('ccavenue.payment.response');
Route::get('ccavenue/payment/failure', [CcavenueController::class , 'failure'])->name('ccavenue.payment.cancel');


//payphone
Route::group(['prefix' => 'payphone'], function () {
    Route::get('/', [PayphoneController::class , 'index'])->name('payphone.checkout');
    Route::get('/payment-success', [PayphoneController::class , 'payphoneCheckout'])->name('payphone.success');
});


// myfatoora
Route::get('myfatoora', [MyFatooraController::class , 'myfatoora'])->name('myfatoora');
Route::post('myfatoora-checkout', [MyFatooraController::class , 'myfatooraCheckout'])->name('myfatoora.checkout.process');
Route::get('myfatoora-checkout-success', [MyFatooraController::class , 'myfatooraCheckoutSuccess'])->name('myfatoora.checkout.success');

//easypaisa
Route::group(['prefix' => 'easypaisa'], function () {
    Route::get('/', [EasypaisaController::class , 'index'])->name('easypaisa.checkout');
    Route::get('/payment-success', [EasypaisaController::class , 'easypaisaCheckout'])->name('easypaisa.success');
});

// paymongo
Route::get('paymongo', [PaymongoController::class , 'paymongo'])->name('paymongo');
Route::post('paymongo-checkout', [PaymongoController::class , 'paymongoCheckout'])->name('paymongo.checkout.process');
Route::get('paymongo-checkout-success', [PaymongoController::class , 'paymongoCheckoutSuccess'])->name('paymongo.checkout.success');

// Fedapay
Route::get('fedapay', [FedaPayController::class , 'fedapay'])->name('fedapay');
Route::post('fedapay-checkout', [FedaPayController::class , 'fedapayCheckout'])->name('fedapay.checkout.process');
Route::get('fedapay-checkout-success', [FedaPayController::class , 'fedapayCheckoutSuccess'])->name('fedapay.checkout.success');

// SSLCOMMERZ
Route::get('/sslcommerz', [SslCommerzController::class , 'create'])->name('sslcommerz');
Route::post('/sslcommerz/initialize', [SslCommerzController::class , 'initialize'])->name('sslcommerz.initialize');
Route::any('/sslcommerz/payment/success', [SslCommerzController::class , 'success'])->name('sslcommerz.success');
Route::any('/sslcommerz/payment/failure', [SslCommerzController::class , 'failure'])->name('sslcommerz.failure');
Route::any('/sslcommerz/payment/cancel', [SslCommerzController::class , 'cancel'])->name('sslcommerz.cancel');
Route::any('/sslcommerz/payment/ipn', [SslCommerzController::class , 'ipn'])->name('sslcommerz.ipn');

// PawaPay
Route::get('/pawapay', [PawaPayController::class, 'pawapay'])->name('pawapay');
Route::post('/pawapay/initiate', [PawaPayController::class, 'initiate'])->name('pawapay.initiate');
Route::get('/pawapay/status/{transaction_id}', [PawaPayController::class, 'status'])->name('pawapay.status');
Route::any('/pawapay/webhook', [PawaPayController::class, 'webhook'])->name('pawapay.webhook');

Route::view("success", 'success');
Route::view("failure", 'failure')->name('failure');
Route::view("pending", 'pending');
