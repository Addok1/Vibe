<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Install\InstallController;

/*
|--------------------------------------------------------------------------
| Install Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/*
 * These routes use the root namespace 'App\Http\Controllers\Web'.
 */
Route::group([],function () {

    Route::get('/', [InstallController::class, 'index']);

    Route::post('/verfication-submit', [InstallController::class, 'verification_submit'])
        ->name('verfication-submit');

});
