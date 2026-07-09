<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Common\TranslationController;
use App\Http\Controllers\Api\V1\Common\ServiceLocationController;


/*
|--------------------------------------------------------------------------
| Common API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with 'api/v1'.
| These routes use the root namespace 'App\Http\Controllers\Api\V1'.
|
 */

/*
         * Root namespace 'App\Http\Controllers\Api\V1\Common'.
    */
Route::namespace('Common')->group(function () {



    // Get Language translation for mobile
  Route::get('translation/get', [TranslationController::class, 'index']); 
  Route::get('translation-user/get', [TranslationController::class, 'userIndex']); 
  Route::get('translation/list', [TranslationController::class, 'listLocalizations']);



    Route::middleware(['auth:sanctum','throttle:120,1'])->group(function () {

        // Get all the ServiceLocation.
        Route::get('servicelocation', [ServiceLocationController::class, 'index']);

    });



});
