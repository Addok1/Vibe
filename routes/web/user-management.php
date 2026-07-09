<?php

use App\Http\Controllers\UserImportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // user
    Route::group(['prefix' => 'users'], function () {
        Route::middleware(['permission:view-users'])->get('/', [UserController::class, 'index'])->name('users.index');
        Route::middleware('remove_empty_query')->get('/list', [UserController::class, 'list'])->name('users.list');
        Route::middleware(['permission:add-user'])->get('/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/store', [UserController::class, 'store'])->name('users.store');
        Route::middleware(['permission:edit-user'])->get('/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/update/{user}', [UserController::class, 'update'])->name('users.update');
        Route::middleware(['permission:edit-user'])->get('/password/edit/{id}', [UserController::class, 'editPassword'])->name('users.password.edit');
        Route::post('/password/update/{user}', [UserController::class, 'updatePasswords'])->name('users.password.update');
        Route::get('/check-mobile/{mobile}', [UserController::class, 'checkMobileExists']);
        Route::get('/check-email/{email}', [UserController::class, 'checkEmailExists']);
        Route::get('/check-mobile/{mobile}/{id}', [UserController::class, 'checkMobileExists'])->name('users.checkMobileExists');
        Route::get('/check-email/{email}/{id}', [UserController::class, 'checkEmailExists'])->name('users.checkEmailExists');

        Route::post('/update-status', [UserController::class, 'updateStatus'])->name('users.updateStatus');
        Route::post('/toggle-approve', [UserController::class, 'toggleApprove'])->name('users.toggleApprove');
        Route::post('/update/decline/reason', [UserController::class, 'UpdateUserDeclineReason'])->name('users.UpdateUserDeclineReason');
        Route::middleware(['permission:view-user-profile'])->get('/view-profile/{user}', [UserController::class, 'viewProfile'])->name('users.view-profile');
        Route::delete('/delete/{user}', [UserController::class, 'destroy']);
        // wallet-history/list
        Route::post('/wallet-add-amount/{user}', [UserController::class, 'walletAddAmount'])->name('users.addAmount');

        Route::get('/wallet-history/list/{user}', [UserController::class, 'walletHistoryList'])->name('users.walletHistoryList');

        Route::get('/request/list/{user}', [UserController::class, 'requestList'])->name('users.requestList');

        Route::get('/rating-list/{user}', [UserController::class, 'ratinghistory'])->name('users.ratinghistory');

        Route::middleware(['permission:delete-user'])->get('/deleted-user', [UserController::class, 'deletedUser'])->name('users.deleted-users');
        Route::get('/deletedList', [UserController::class, 'deletedList'])->name('users.deletedList');
        // Route::get('/user-dashboard', [UserController::class, 'dashboard'])->name('users.dashboard');
        Route::patch('/restore/{id}', [UserController::class, 'restoreUser'])->name('users.restore');
    });

    Route::get('/profile-edit',  [UserController::class, 'profileEdit']);
    // update-profile
    Route::post('/update-profile',  [UserController::class, 'updateProfile']);
    Route::post('/update-password',  [UserController::class, 'updatePassword']);


    //user Bulk Upload
    Route::group(['prefix' => 'user-import', 'middleware' => 'permission:user-import'], function () {
        Route::get('/', [UserImportController::class, 'index'])->name('userImport.index');
        Route::middleware(['permission:add_banner_image'])->get('/create', [UserImportController::class, 'create'])->name('userImport.create');
        Route::get('/list', [UserImportController::class, 'list'])->name('userImport.list');
        Route::post('/import-file', [UserImportController::class, 'import'])->name('userImport.import');
        Route::get('/download-invalid-file/{id}', [UserImportController::class, 'downloadInvalidFile'])->name('userImport.downloadInvalidFile');
        Route::get('/reupload-invalid-file/{id}', [UserImportController::class, 'reuploadInvalidFile'])->name('userImport.reuploadInvalidFile');
        Route::post('/reupload-invalid-file-store/{userImport}', [UserImportController::class, 'reuploadInvalidFileStore'])->name('userImport.reuploadInvalidFileStore');

        Route::get('/sample-download', [UserImportController::class, 'sampleDownload'])->name('userImport.sampleDownload');
    });


    //user needed document
Route::group(['prefix' => 'user-needed-documents', 'middleware' => 'permission:manage-user-needed-document'],  function () {
    Route::middleware(['permission:manage-user-needed-document'])->get('/', [UserDocumentController::class, 'userNeededDocumentIndex'])->name('userneeddocuments.index');
    Route::middleware('remove_empty_query')->get('/list', [UserDocumentController::class, 'userNeededDocumentList'])->name('userneeddocuments.list');
    Route::middleware(['permission:add-user-needed-document'])->get('/create', [UserDocumentController::class, 'userNeededDocumentCreate'])->name('userneeddocuments.create');
    Route::post('/store', [UserDocumentController::class, 'userNeededDocumentStore'])->name('userneeddocuments.store');
    Route::middleware(['permission:edit-user-needed-document'])->get('/edit/{document}', [UserDocumentController::class, 'userNeededDocumentEdit'])->name('userneeddocuments.edit');
    Route::post('/update/{document}', [UserDocumentController::class, 'userNeededDocumentUpdate'])->name('userneeddocuments.update');
    Route::post('/toggle', [UserDocumentController::class, 'userNeededDocumentToggle'])->name('userneeddocuments.updatestatus');
    Route::delete('/delete/{document}', [UserDocumentController::class, 'userNeededDocumentDelete'])->name('userneeddocuments.delete');
});

// user document
Route::group(['prefix'=>'user', 'middleware' => 'permission:manage-user-needed-document'],  function () {
    Route::post('/update-approve', [UserDocumentController::class, 'userApprovalToggle'])->name('user.userApprovalToggle');
    Route::middleware(['permission:view-user-document'])->get('/document/{user}', [UserDocumentController::class, 'userViewDocument'])->name('user.ViewDocument');
    Route::middleware(['permission:view-user-document'])->get('/document/list/{user}', [UserDocumentController::class, 'userDocumentList'])->name('user.userDocumentList');
    Route::middleware('remove_empty_query')->get('document/list/{userId}', [UserDocumentController::class, 'documentList'])->name('user.listDocument');

    Route::middleware(['permission:view-user-document'])->get('/document-upload/{document}/{userId}', [UserDocumentController::class, 'documentUpload'])->name('user.documentUpload');
    Route::post('/document-upload/{document}/{userId}', [UserDocumentController::class, 'documentUploadStore'])->name('user.documentUploadStore');
    Route::post('/document-toggle/{documentId}/{userId}/{status}', [UserDocumentController::class, 'approveUserDocument'])->name('user.approveUserDocument');
    Route::get('/update-documents/{userId}', [UserDocumentController::class, 'updateAndApprove']);
    Route::get('/document-toggle/{userId}',[UserDocumentController::class, 'approveDocumentStatus']);
    
});

});
