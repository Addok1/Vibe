<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //reports
    Route::group(['prefix' => 'report', 'middleware' => 'permission:report-management'], function () {
        Route::middleware(['permission:user-report'])->get('/user-report', [ReportController::class, 'userReport'])->name('report.userReport');
        Route::post('/user-report-download', [ReportController::class, 'userReportDownload'])->name('report.userReportDownload');
        Route::middleware(['permission:driver-report'])->get('/driver-report', [ReportController::class, 'driverReport'])->name('report.driverReport');
        Route::post('/driver-report-download', [ReportController::class, 'driverReportDownload'])->name('report.driverReportDownload');
        Route::get('/getVehicleTypes', [ReportController::class, 'getVehicleTypes'])->name('report.getVehicletypes');
        Route::middleware(['permission:owner-report'])->get('/owner-report', [ReportController::class, 'ownerReport'])->name('report.ownerReport');
        Route::post('/owner-report-download', [ReportController::class, 'ownerReportDownload'])->name('report.ownerReportDownload');
        Route::middleware(['permission:finance-report'])->get('/finance-report', [ReportController::class, 'financeReport'])->name('report.financeReport');
        Route::post('/finance-report-download', [ReportController::class, 'financeReportDownload'])->name('report.financeReportDownload');
        Route::middleware(['permission:fleet-report'])->get('/fleet-report', [ReportController::class, 'fleetReport'])->name('report.fleetReport');
        Route::get('/list-fleets', [ReportController::class, 'listFleet'])->name('report.listFleet');
        Route::post('/fleet-report-download', [ReportController::class, 'fleetReportDownload'])->name('report.fleetReportDownload');
        Route::get('/franchiseowner-report', [ReportController::class, 'franchiseownerReport'])->name('report.franchiseownerReport');
        Route::post('/franchiseowner-report-download', [ReportController::class, 'franchiseownerReportDownload'])->name('report.franchiseownerReportDownload');

        Route::middleware(['permission:driver-duty-report'])->get('/driver-duty-report', [ReportController::class, 'driverDutyReport'])->name('report.driverDutyReport');
        Route::post('/driver-duty-report-download', [ReportController::class, 'driverDutyReportDownload'])->name('report.driverDutyReportDownload');
        Route::get('/getDrivers', [ReportController::class, 'getDrivers'])->name('report.getDrivers');

        // Route::get('view-invoice/{request_detail}',[ReportController::class, 'downloadInvoice']);
        Route::post('/download-invoice', [ReportController::class, 'downloadInvoice']);
    });
    Route::get('/download-pdf', [ReportController::class, 'downloadPdf'])->name('download.pdf');
    Route::post('/download-invoice', [ReportController::class, 'downloadInvoice']);
});
