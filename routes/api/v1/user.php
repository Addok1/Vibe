<?php
use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\TripRequestController;
use App\Http\Controllers\Api\V1\User\AccountController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\UserDocumentController;

/*
 * These routes are prefixed with 'api/v1/user'.
 * These routes use the root namespace 'App\Http\Controllers\Api\V1\User'.
 * These routes use the middleware group 'auth'.
 */
Route::prefix('user')->group(function () {

    // Logged‑in user info
    Route::middleware(['auth:sanctum', 'throttle:120,1'])
        ->get('/', [AccountController::class, 'me']);

    // Authenticated user routes
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

        // Profile updates
        Route::post('profile', [ProfileController::class, 'updateProfile']);
        Route::post('driver-profile', [ProfileController::class, 'updateDriverProfile']);
        Route::post('update-my-lang', [ProfileController::class, 'updateMyLanguage']);
        Route::post('update-bank-info', [ProfileController::class, 'updateBankinfo']);
        Route::get('get-bank-info', [ProfileController::class, 'getBankInfo']);

        // Favourite locations
        Route::get('list-favourite-location', [ProfileController::class, 'FavouriteLocationList']);
        Route::post('add-favourite-location', [ProfileController::class, 'addFavouriteLocation']);
        Route::get('delete-favourite-location/{favourite_location}', [ProfileController::class, 'deleteFavouriteLocation']);

        // Account actions
        Route::post('delete-user-account', [ProfileController::class, 'userDeleteAccount']);
        Route::post('update-location', [ProfileController::class, 'updateLocation']);

        // get User Document
        Route::get('documents/needed', [UserDocumentController::class, 'index']);
        // Upload User document
        Route::post('upload/documents', [UserDocumentController::class, 'uploadDocuments']);

        // Invoice download
        Route::get('download-invoice/{requestId}', [TripRequestController::class, 'mobileShareInvoice']);
    });
});


