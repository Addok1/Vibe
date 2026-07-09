<?php

use App\Http\Controllers\ManageOwnerController;
use App\Http\Controllers\ManageFleetController;
use App\Http\Controllers\FleetDriverController;
use App\Http\Controllers\OwnerManagementController;
use App\Http\Controllers\Api\V1\Request\EtaController;
use App\Http\Controllers\OwnerDispatcherController;
use App\Http\Controllers\Api\V1\Request\PromoCodeController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {


    //owner needed document
    Route::group(['prefix' => 'owner-needed-documents', 'middleware' => 'permission:manage-owner-needed-document'], function () {
        Route::get('/', [OwnerManagementController::class, 'ownerNeededDocumentIndex'])->name('ownerneeddocuments.index');
        Route::middleware('remove_empty_query')->get('/list', [OwnerManagementController::class, 'ownerNeededDocumentList'])->name('ownerneeddocuments.list');
        Route::middleware(['permission:add-owner-needed-document'])->get('/create', [OwnerManagementController::class, 'ownerNeededDocumentCreate'])->name('ownerneeddocuments.create');
        Route::post('/store', [OwnerManagementController::class, 'ownerNeededDocumentStore'])->name('ownerneeddocuments.store');
        Route::middleware(['permission:edit-owner-needed-document'])->get('/edit/{document}', [OwnerManagementController::class, 'ownerNeededDocumentEdit'])->name('ownerneeddocuments.edit');
        Route::post('/update/{document}', [OwnerManagementController::class, 'ownerNeededDocumentUpdate'])->name('ownerneeddocuments.update');
        Route::post('/toggle', [OwnerManagementController::class, 'ownerNeededDocumentToggle'])->name('ownerneeddocuments.updatestatus');
        Route::delete('/delete/{document}', [OwnerManagementController::class, 'ownerNeededDocumentDelete'])->name('ownerneeddocuments.delete');
    });

    // manage owners
    Route::group(['prefix' => 'manage-owners', 'middleware' => 'permission:owner-management'], function () {
        Route::get('/', [ManageOwnerController::class, 'index'])->name('manageowners.index');
        Route::middleware(['permission:add_manage_owners'])->get('/create', [ManageOwnerController::class, 'create'])->name('manageowners.Create');
        Route::middleware('remove_empty_query')->get('/list', [ManageOwnerController::class, 'list'])->name('manageowners.list');
        Route::post('/store', [ManageOwnerController::class, 'store'])->name('manageowners.store');
        Route::middleware(['permission:edit-owner'])->get('/edit/{owner}', [ManageOwnerController::class, 'edit'])->name('manageowners.edit');
        Route::post('/update/{owner}', [ManageOwnerController::class, 'update'])->name('manageowners.update');
        Route::middleware(['permission:edit-owner'])->get('/password/edit/{owner}', [ManageOwnerController::class, 'editPassword'])->name('owner.password.edit');
        Route::post('/password/update/{owner}', [ManageOwnerController::class, 'updatePasswords'])->name('owner.password.update');
        Route::post('/approve/{owner}', [ManageOwnerController::class, 'approve'])->name('manageowners.approve');
        Route::middleware(['permission:delete-owner'])->delete('/delete/{owner}', [ManageOwnerController::class, 'delete'])->name('manageowners.delete');
        Route::middleware(['permission:view-owner-document'])->get('/document/{owner}', [ManageOwnerController::class, 'document'])->name('manageowners.document');
        Route::get('/document/list/{owner}', [ManageOwnerController::class, 'driverDocumentList'])->name('manageowners.driverDocumentList');
        Route::get('/check-mobile/{mobile}', [ManageOwnerController::class, 'checkMobileExists']);
        Route::get('/check-email/{email}', [ManageOwnerController::class, 'checkEmailExists']);
        Route::get('/check-mobile/{mobile}/{id}', [ManageOwnerController::class, 'checkMobileExists'])->name('manageowners.checkMobileExists');
        Route::get('/check-email/{email}/{id}', [ManageOwnerController::class, 'checkEmailExists'])->name('manageowners.checkEmailExists');
        Route::middleware(['permission:view-owner-document'])->get('/document-upload/{document}/{ownerId}', [ManageOwnerController::class, 'documentUpload'])->name('manageowners.documentUpload');
        Route::post('/document-upload/{document}/{ownerId}', [ManageOwnerController::class, 'documentUploadStore'])->name('manageowners.documentUploadStore');
        Route::post('/document-toggle/{documentId}/{ownerId}/{status}', [ManageOwnerController::class, 'approvOwnerDocument'])->name('manageowners.approveOwnerDocument');
        Route::get('/update/{ownerId}', [ManageOwnerController::class, 'updateAndApprove']);
        Route::get('/owner-payment-history/{owner}', [ManageOwnerController::class, 'ownerPaymentHistory'])->name('manageowners.ownerPaymentHistory');
        Route::middleware(['permission:view-owner-profile'])->get('/view-profile/{owner}', [ManageOwnerController::class, 'viewProfile'])->name('manageowners.viewProfile');
        Route::post('/update-status', [ManageOwnerController::class, 'updateStatus'])->name('manageowners.updateStatus');
        Route::get('/view-owner-driver/list/{owner}', [ManageOwnerController::class, 'driverList'])->name('manageowners.driverList');
        Route::get('/view-owner-fleet/list/{owner}', [ManageOwnerController::class, 'fleetList'])->name('manageowners.fleetList');

        // wallet-history/list
        Route::post('/wallet-add-amount/{owner}', [ManageOwnerController::class, 'walletAddAmount'])->name('manageowners.addAmount');

        Route::get('/wallet-history/list/{owner}', [ManageOwnerController::class, 'walletHistoryList'])->name('manageowners.walletHistoryList');
        Route::get('/deleted-owner', [ManageOwnerController::class, 'deletedOwner'])->name('owner.deleted-owner');
        Route::get('/deletedList', [ManageOwnerController::class, 'deletedList'])->name('owner.deletedList');
        Route::patch('/restore/{id}', [ManageOwnerController::class, 'restoreOwner'])->name('owner.restore');
    });
    //withdrawal request owners
    Route::group(['prefix' => 'withdrawal-request-owners', 'middleware' => 'permission:manage-owner'], function () {
        Route::get('/', [ManageOwnerController::class, 'WithdrawalRequestOwnersIndex'])->name('withdrawalrequestOwners.index');
        Route::middleware('remove_empty_query')->get('/list', [ManageOwnerController::class, 'WithdrawalRequestOwnersList'])->name('withdrawalrequestOwners.list');
        Route::get('/view-in-detail/{owner}', [ManageOwnerController::class, 'WithdrawalRequestOwnersViewDetails'])->name('withdrawalrequestOwners.ViewDetails');
        //updatePaymentStatus
        Route::middleware('remove_empty_query')->get('/amounts/{owner_id}', [ManageOwnerController::class, 'WithdrawalRequestAmount'])->name('withdrawalrequestOwner.list');
        Route::post('/update-status', [ManageOwnerController::class, 'updatePaymentStatus'])->name('withdrawalrequestOwners.updateStatus');
    });




    //fleet needed documents
    Route::group(['prefix' => 'fleet-needed-documents', 'middleware' => 'permission:fleet-driver-document-view'], function () {
        Route::get('/', [ManageFleetController::class, 'fleetNeededDocumentIndex'])->name('fleetneeddocuments.index');
        Route::middleware('remove_empty_query')->get('/list', [ManageFleetController::class, 'fleetNeededDocumentList'])->name('fleetneeddocuments.list');
        Route::middleware(['permission:add-fleet-driver-document'])->get('/create', [ManageFleetController::class, 'fleetNeededDocumentCreate'])->name('fleetneeddocuments.create');
        Route::post('/store', [ManageFleetController::class, 'fleetNeededDocumentStore'])->name('fleetneeddocuments.store');
        Route::middleware(['permission:edit-fleet-driver-document'])->get('/edit/{document}', [ManageFleetController::class, 'fleetNeededDocumentEdit'])->name('fleetneeddocuments.edit');
        Route::post('/update/{document}', [ManageFleetController::class, 'fleetNeededDocumentUpdate'])->name('fleetneeddocuments.update');
        Route::post('/toggle', [ManageFleetController::class, 'fleetNeededDocumentToggle'])->name('fleetneeddocuments.updatestatus');
        Route::middleware(['permission:delete-fleet-driver-document'])->delete('/delete/{document}', [ManageFleetController::class, 'fleetNeededDocumentDelete'])->name('fleetneeddocuments.delete');
    });

    // manage fleet
    Route::group(['prefix' => 'manage-fleet', 'middleware' => 'permission:view-fleet'], function () {
        Route::get('/', [ManageFleetController::class, 'index'])->name('managefleets.index');
        Route::middleware(['permission:add-fleet'])->get('/create', [ManageFleetController::class, 'create'])->name('managefleets.Create');
        Route::middleware('remove_empty_query')->get('/list', [ManageFleetController::class, 'list'])->name('managefleets.list');
        Route::post('/store', [ManageFleetController::class, 'store'])->name('managefleets.store');
        Route::middleware(['permission:edit-fleet'])->get('/edit/{fleet}', [ManageFleetController::class, 'edit'])->name('managefleets.edit');
        Route::post('/update/{fleet}', [ManageFleetController::class, 'update'])->name('managefleets.update');
        Route::post('/assign/{fleet}/{driver}', [ManageFleetController::class, 'assignDriver'])->name('managefleets.assignDriver');
        Route::post('/approve/{fleet}', [ManageFleetController::class, 'approve'])->name('managefleets.approve');
        Route::delete('/delete/{fleet}', [ManageFleetController::class, 'delete'])->name('managefleets.delete');
        Route::middleware(['permission:view-fleet-document'])->get('/document/{fleet}', [ManageFleetController::class, 'document'])->name('managefleets.document');
        Route::get('/document/list/{fleet}', [ManageFleetController::class, 'listDocument'])->name('managefleets.listDocument');
        Route::get('/listFleetDriver/{fleet}', [ManageFleetController::class, 'listFleetDrivers'])->name('managefleets.listFleetDrivers');

        Route::get('/document-upload/{document}/{fleetId}', [ManageFleetController::class, 'documentUpload'])->name('managefleets.documentUpload');
        Route::post('/document-upload/{document}/{fleetId}', [ManageFleetController::class, 'documentUploadStore'])->name('managefleets.documentUploadStore');
        Route::post('/document-toggle/{documentId}/{fleetId}/{status}', [ManageFleetController::class, 'approvfleetDocument'])->name('managefleets.approvefleetDocument');
        Route::get('/update-document/{fleetId}', [ManageFleetController::class, 'updateAndApprove']);
        Route::get('/fleet-payment-history/{fleet}', [ManageFleetController::class, 'fleetPaymentHistory'])->name('managefleets.fleetPaymentHistory');
        Route::get('/documents/{fleet}', [ManageFleetController::class, 'approveDocumentStatus'])->name('managefleets.approveDocumentStatus');
    });
    //fleet drivers
    Route::group(['prefix' => 'fleet-drivers', 'middleware' => 'permission:view-approved-fleet-drivers'], function () {
        Route::get('/', [FleetDriverController::class, 'index'])->name('approvedFleetdriver.Index');
        Route::get('/pending', [FleetDriverController::class, 'pendingIndex'])->name('approvedFleetdriver.pendingIndex');
        Route::middleware('remove_empty_query')->get('list', [FleetDriverController::class, 'listDrivers'])->name('fleet-drivers.list');
        Route::post('/store', [FleetDriverController::class, 'store'])->name('fleet-drivers.store');
        Route::middleware(['permission:edit-fleet-drivers'])->get('/edit/{driver}', [FleetDriverController::class, 'edit'])->name('fleet-drivers.edit');
        Route::middleware(['permission:view-fleet-driver-profile'])->get('/view-profile/{driver}', [FleetDriverController::class, 'viewProfile'])->name('fleet-drivers.viewProfile');
        Route::middleware(['permission:add-fleet-drivers'])->get('create', [FleetDriverController::class, 'create'])->name('fleet-drivers.create');
        Route::post('/update/{driver}', [FleetDriverController::class, 'update'])->name('fleet-drivers.update');
        Route::post('/approve/{driver}', [FleetDriverController::class, 'approve'])->name('fleet-drivers.approve');
        Route::delete('/delete/{driver}', [FleetDriverController::class, 'delete'])->name('fleet-drivers.delete');
        Route::get('/ownerList', [FleetDriverController::class, 'listOwnersByLocation'])->name('fleet-drivers.listOwnersByLocation');
        Route::get('/list-owners', [FleetDriverController::class, 'listOwners'])->name('fleet-drivers.listOwners');
        Route::get('/document/{driver}', [FleetDriverController::class, 'approvedDriverViewDocument'])->name('approvedFleetdriver.ViewDocument');
        Route::get('/document/list/{driver}', [FleetDriverController::class, 'driverDocumentList'])->name('approvedFleetdriver.driverDocumentList');
        Route::middleware('remove_empty_query')->get('document/list/{driverId}', [FleetDriverController::class, 'documentList'])->name('approvedFleetdriver.listDocument');
        Route::get('/document-upload/{document}/{driverId}', [FleetDriverController::class, 'documentUpload'])->name('approvedFleetdriver.documentUpload');
        Route::post('/document-upload-store/{document}/{driverId}', [FleetDriverController::class, 'documentUploadStore'])->name('approvedFleetdriver.documentUploadStore');
        Route::post('/document-toggle/{documentId}/{driverId}/{status}', [FleetDriverController::class, 'approveDriverDocument'])->name('approvedFleetdriver.approveDriverDocument');
        Route::get('/update/{driverId}', [FleetDriverController::class, 'updateAndApprove']);
        Route::get('/pending-drivers', [FleetDriverController::class, 'pendingDriverIndex'])->name('pendingdriver.fleetIndex');
        Route::get('/password/edit/{driver}', [FleetDriverController::class, 'editPassword'])->name('fleet-drivers.password.edit');
        Route::post('/password/update/{driver}', [FleetDriverController::class, 'updatePasswords'])->name('fleet-drivers.password.update');
        Route::post('update/decline/reason', [FleetDriverController::class, 'UpdateDriverDeclineReason'])->name('approvedFleetdriver.UpdateDriverDeclineReason');

        Route::get('/documents/{driver}', [FleetDriverController::class, 'approveDocumentStatus'])->name('approvedFleetdriver.approveDocumentStatus');

        Route::get('/check-mobile/{mobile}', [FleetDriverController::class, 'checkMobileExists']);
        Route::get('/check-email/{email}', [FleetDriverController::class, 'checkEmailExists']);
        Route::get('/check-mobile/{mobile}/{id}', [FleetDriverController::class, 'checkMobileExists'])->name('approvedFleetdriver.checkMobileExists');
        Route::get('/check-email/{email}/{id}', [FleetDriverController::class, 'checkEmailExists'])->name('approvedFleetdriver.checkEmailExists');
    });
});

Route::group(['prefix' => 'owner'], function () {
    Route::middleware(['permission:owner-booking'])->get('bookride', [OwnerDispatcherController::class, 'bookrides'])->name('ownerDispatcher.bookrides');

    Route::middleware('auth:sanctum')->post('request/eta', [EtaController::class, 'eta']);
    Route::post('request/list_packages', [EtaController::class, 'listPackages']);
    Route::post('serviceVerify', [EtaController::class, 'serviceVerify']);
    Route::get('fetch-user-detail', [OwnerDispatcherController::class, 'fetchUserIfExists']);
    Route::post('/create-request', [OwnerDispatcherController::class, 'createRequests']);
    Route::get('promocode-list', [PromoCodeController::class, 'index']);
    Route::post('promocode-redeem', [PromoCodeController::class, 'redeem']);
    Route::post('promocode-clear', [PromoCodeController::class, 'clear']);
});
