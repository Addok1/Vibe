<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        'api/*',
        'sslcommerz/payment/success',
        'sslcommerz/payment/failure',
        'sslcommerz/payment/cancel',
        'sslcommerz/payment/ipn',
        'pawapay/webhook',
        'social/*/callback',
    ];
}
