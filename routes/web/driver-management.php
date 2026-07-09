<?php

use App\Http\Controllers\BankInfoController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\DriverManagementController;
use App\Http\Controllers\DriverImportController;
use App\Http\Controllers\Web\IncentiveController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    // Driver Dashboard (analytics under Driver Management)
    Route::middleware(['permission:drivers-management'])->get('/driver-dashboard', [DriverDashboardController::class, 'index'])->name('driver-dashboard');
    Route::middleware(['permission:drivers-management'])->get('/driver-dashboard/analytics', [DriverDashboardController::class, 'analytics'])->name('driver-dashboard.analytics');
    Route::middleware(['permission:drivers-management'])->get('/driver-dashboard/export', [DriverDashboardController::class, 'export'])->name('driver-dashboard.export');

    // approved drivers
    Route::group(['prefix' => 'approved-drivers'], function () {
        Route::middleware(['permission:view-approved-drivers'])->get('/', [DriverManagementController::class, 'approvedDriverIndex'])->name('approveddriver.Index');
        Route::middleware(['permission:add-driver'])->get('/create', [DriverManagementController::class, 'create'])->name('approveddriver.create');
        Route::post('/store', [DriverManagementController::class, 'store'])->name('approveddriver.store');
        Route::middleware(['permission:edit-driver'])->get('/edit/{id}', [DriverManagementController::class, 'edit'])->name('approveddriver.edit');
        Route::post('/update/{driver}', [DriverManagementController::class, 'update'])->name('approveddriver.update');
        Route::middleware(['permission:edit-driver'])->get('/password/edit/{id}', [DriverManagementController::class, 'editPassword'])->name('drivers.password.edit');
        Route::post('/password/update/{driver}', [DriverManagementController::class, 'updatePasswords'])->name('drivers.password.update');
        Route::post('/disapprove/{driver}', [DriverManagementController::class, 'disapprove'])->name('approveddriver.disapprove');
        Route::middleware(['permission:view-driver-profile'])->get('/view-profile/{driver}', [DriverManagementController::class, 'viewProfile'])->name('approveddriver.viewProfile');
        Route::middleware(['permission:driver-upload-documents'])->get('/document-upolad', [DriverManagementController::class, 'uploadDocument'])->name('approveddriver.uploadDocument');
        Route::get('/check-mobile/{mobile}', [DriverManagementController::class, 'checkMobileExists']);
        Route::get('/check-email/{email}', [DriverManagementController::class, 'checkEmailExists']);
        Route::get('/check-mobile/{mobile}/{id}', [DriverManagementController::class, 'checkMobileExists'])->name('approveddriver.checkMobileExists');
        Route::get('/check-email/{email}/{id}', [DriverManagementController::class, 'checkEmailExists'])->name('approveddriver.checkEmailExists');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'list'])->name('approveddriver.list');
        Route::post('update/decline/reason', [DriverManagementController::class, 'UpdateDriverDeclineReason'])->name('approveddriver.UpdateDriverDeclineReason');

        Route::middleware(['permission:driver-upload-documents'])->get('/document/{driver}', [DriverManagementController::class, 'approvedDriverViewDocument'])->name('approveddriver.ViewDocument');
        Route::middleware(['permission:driver-upload-documents'])->get('/document/list/{driver}', [DriverManagementController::class, 'driverDocumentList'])->name('approveddriver.driverDocumentList');
        Route::middleware('remove_empty_query')->get('document/list/{driverId}', [DriverManagementController::class, 'documentList'])->name('approveddriver.listDocument');

        Route::middleware(['permission:driver-upload-documents'])->get('/document-upload/{document}/{driverId}', [DriverManagementController::class, 'documentUpload'])->name('approveddriver.documentUpload');
        Route::post('/document-upload/{document}/{driverId}', [DriverManagementController::class, 'documentUploadStore'])->name('approveddriver.documentUploadStore');
        Route::post('/document-toggle/{documentId}/{driverId}/{status}', [DriverManagementController::class, 'approveDriverDocument'])->name('approveddriver.approveDriverDocument');

        Route::get('/update-documents/{driverId}', [DriverManagementController::class, 'updateAndApprove']);


        Route::delete('/delete/{driver}', [DriverManagementController::class, 'destroy']);
        // wallet-history/list
        Route::post('/wallet-add-amount/{driver}', [DriverManagementController::class, 'walletAddAmount'])->name('approveddriver.addAmount');

        Route::get('/wallet-history/list/{driver}', [DriverManagementController::class, 'walletHistoryList'])->name('approveddriver.walletHistoryList');
        Route::get('/request/list/{driver}', [DriverManagementController::class, 'requestList'])->name('approveddrivers.requestList');
        Route::patch('/restore/{id}', [DriverManagementController::class, 'restoreUser'])->name('approveddrivers.restore');

        Route::get('/documents/{driver}', [DriverManagementController::class, 'approveDocumentStatus'])->name('approveddriver.approveDocumentStatus');
    });


    //pending drivers
    Route::group(['prefix' => 'pending-drivers', 'middleware' => 'permission:view-approval-pending-drivers'], function () {
        Route::get('/', [DriverManagementController::class, 'pendingDriverIndex'])->name('pendingdriver.indexIndex');
    });

    //pending drivers
    Route::group(['prefix' => 'drivers-levelup', 'middleware' => 'permission:view-drivers-levelup'], function () {
        // Route::get('/', [DriverManagementController::class, 'driverLevelUpIndex'])->name('driverlevelup.index');     
        Route::get('/list', [DriverManagementController::class, 'driverLevelList'])->name('driverlevelup.list');
        Route::get('/{zoneType}', [DriverManagementController::class, 'driverLevelUpIndex'])->name('driverlevelup.index');
        // Route::get('/list/{zoneType}', [DriverManagementController::class, 'driverLevelList'])->name('driverlevelup.list');
        Route::post('/store', [DriverManagementController::class, 'driverLevelStore'])->name('approveddriver.driverLeveStore');
        Route::middleware(['permission:edit-drivers-levelup'])->get('/edit/{level}', [DriverManagementController::class, 'driverLevelEdit'])->name('driverlevelup.edit');
        Route::post('/settingsUpdate', [DriverManagementController::class, 'settingsUpdate'])->name('driverlevelup.settingsUpdate');
        Route::post('/update/{level}', [DriverManagementController::class, 'driverLevelUpdate'])->name('approveddriver.driverLevelUpdate');
        Route::delete('/delete/{level}', [DriverManagementController::class, 'driverLevelDelete'])->name('approveddriver.driverLevelDelete');
        Route::middleware(['permission:add-drivers-levelup'])->get('/create/{zoneType}', [DriverManagementController::class, 'driverLevelUpCreate'])->name('driverlevelup.create');
    });


    //drivers rating
    Route::group(['prefix' => 'drivers-rating', 'middleware' => 'permission:driver-rating-list'], function () {
        Route::get('/', [DriverManagementController::class, 'driverRatingIndex'])->name('driversrating.driverRatingIndex');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'driverRatingList'])->name('driversrating.list');
        Route::middleware(['permission:view-driver-profile'])->get('/view-profile/{driver}', [DriverManagementController::class, 'viewDriverRating'])->name('driversrating.viewDriverRating');
        Route::get('/request-list/{driver}', [DriverManagementController::class, 'driverRatinghistory'])->name('driversRequestRating.history');
    });

    //delete request drivers
    Route::group(['prefix' => 'delete-request-drivers', 'middleware' => 'permission:delete-request-drivers'], function () {
        Route::get('/', [DriverManagementController::class, 'deleteRequestDriversIndex'])->name('deleterequestdrivers.index');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'deleteRequestList'])->name('deleterequestdrivers.list');
        Route::delete('/delete/{driver}', [DriverManagementController::class, 'destroyDriver'])->name('deleterequestdrivers.destroyDriver');
    });

    //driver needed document
    Route::group(['prefix' => 'driver-needed-documents', 'middleware' => 'permission:manage-driver-needed-document'], function () {
        Route::get('/', [DriverManagementController::class, 'driverNeededDocumentIndex'])->name('driverneededdocuments.Index');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'driverNeededDocumentList'])->name('driverneededdocuments.list');
        Route::middleware(['permission:add-driver-needed-document'])->get('/create', [DriverManagementController::class, 'driverNeededDocumentCreate'])->name('driverneededdocuments.Create');
        Route::post('/store', [DriverManagementController::class, 'driverNeededDocumentStore'])->name('driverneededdocuments.store');
        Route::post('/update/{driverNeededDocument}', [DriverManagementController::class, 'driverNeededDocumentUpdate'])->name('driverneededdocuments.Update');
        Route::middleware(['permission:edit-driver-needed-document'])->get('/edit/{driverNeededDocument}', [DriverManagementController::class, 'driverNeededDocumentEdit'])->name('driverneededdocuments.edit');
        Route::post('/update-status', [DriverManagementController::class, 'updateDocumentStatus'])->name('driverneededdocuments.updateDocumentStatus');
        Route::delete('/delete/{driverNeededDocument}', [DriverManagementController::class, 'destroyDriverDocument'])->name('driverneededdocuments.destroyDriverDocument');
    });

    //withdrawal request drivers
    Route::group(['prefix' => 'withdrawal-request-drivers', 'middleware' => 'permission:withdrawal-request-drivers'], function () {
        Route::get('/', [DriverManagementController::class, 'WithdrawalRequestDriversIndex'])->name('withdrawalrequestdrivers.index');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'WithdrawalRequestDriversList'])->name('withdrawalrequestdrivers.list');
        Route::get('/view-in-detail/{driver}', [DriverManagementController::class, 'WithdrawalRequestDriversViewDetails'])->name('withdrawalrequestdrivers.ViewDetails');
        //updatePaymentStatus
        Route::middleware('remove_empty_query')->get('/amounts/{driver_id}', [DriverManagementController::class, 'WithdrawalRequestAmount'])->name('withdrawalrequestAmount.list');
        Route::post('/update-status', [DriverManagementController::class, 'updatePaymentStatus'])->name('withdrawalrequest.updateStatus');
    });

    //negative balance drivers
    Route::group(['prefix' => 'negative-balance-drivers', 'middleware' => 'permission:negetive-balance-drivers'], function () {
        Route::get('/', [DriverManagementController::class, 'negativeBalanceDriversIndex'])->name('negativebalancedrivers.index');
        Route::middleware('remove_empty_query')->get('/list', [DriverManagementController::class, 'negativeBalanceDriversList'])->name('negativebalancedrivers.list');
        Route::middleware(['permission:view-driver-profile'])->get('/view-profile/{driver}', [DriverManagementController::class, 'negativeBalanceDriverPaymentHistory'])->name('negativebalancedrivers.payment');
    });

    Route::group(['prefix' => 'manage-payment',], function () {
        Route::get('/', [DriverManagementController::class, 'paymentIndex'])->name('payment-history.index');
        Route::get('/list', [DriverManagementController::class, 'userList'])->name('payment-history.list');
    });

    Route::group(['prefix' => 'driver-bank-info', 'middleware' => 'permission:manage-driver-bank-info'], function () {
        Route::get('/', [BankInfoController::class, 'index'])->name('bank.index');
        Route::get('/list', [BankInfoController::class, 'list'])->name('bank.list');
        Route::middleware(['permission:manage-driver-bank-info'])->get('/create', [BankInfoController::class, 'create'])->name('bank.create');
        Route::post('/store', [BankInfoController::class, 'store'])->name('bank.store');
        Route::middleware(['permission:manage-driver-bank-info'])->get('/edit/{method}', [BankInfoController::class, 'edit'])->name('bank.edit');
        Route::post('/update/{method}', [BankInfoController::class, 'update'])->name('bank.update');
        Route::post('/update-status', [BankInfoController::class, 'updateStatus'])->name('bank.updateStatus');
        Route::delete('/delete/{method}', [BankInfoController::class, 'destroy'])->name('bank.delete');
    });

    //Driver Bulk Upload
    Route::group(['prefix' => 'driver-import', 'middleware' => 'permission:user-import'], function () {
        Route::get('/', [DriverImportController::class, 'index'])->name('driverImport.index');
        Route::middleware(['permission:add_banner_image'])->get('/create', [DriverImportController::class, 'create'])->name('driverImport.create');
        Route::get('/list', [DriverImportController::class, 'list'])->name('driverImport.list');
        Route::post('/import-file', [DriverImportController::class, 'import'])->name('driverImport.import');
        Route::get('/download-invalid-file/{id}', [DriverImportController::class, 'downloadInvalidFile'])->name('driverImport.downloadInvalidFile');
        Route::get('/reupload-invalid-file/{id}', [DriverImportController::class, 'reuploadInvalidFile'])->name('driverImport.reuploadInvalidFile');
        Route::post('/reupload-invalid-file-store/{driverImport}', [DriverImportController::class, 'reuploadInvalidFileStore'])->name('driverImport.reuploadInvalidFileStore');


        Route::get('/sample-download', [DriverImportController::class, 'sampleDownload'])->name('driverImport.sampleDownload');
    });

    Route::group(['prefix' => 'incentives', 'middleware' => 'permission:incentives'], function () {

        Route::get('/{zoneType}', [IncentiveController::class, 'index'])->name('incentives.index');
        Route::post('/update', [IncentiveController::class, 'update'])->name('incentives.update');
    });
     Route::get('/download-driver-report/{driver}', [DriverManagementController::class, 'DownloadDriverReport'])->name('driver.DownloadDriverReport');
});
