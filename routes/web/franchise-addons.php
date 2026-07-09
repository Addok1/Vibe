<?php

use App\Http\Controllers\FranchiseAddonsController;
use Illuminate\Support\Facades\Route;


 //franchise-addons
    Route::group(['prefix' => 'franchise-addons', 'middleware' => 'permission:franchise_addons'], function () {
        Route::get('/', [FranchiseAddonsController::class, 'index'])->name('franchiseAddons.index');
        Route::post('/verfication-submit', [FranchiseAddonsController::class, 'verification_submit'])->name('franchiseAddons.verfication-submit');
        Route::post('/franchise-files', [FranchiseAddonsController::class, 'franchise_files_uploads'])->name('franchiseAddons.franchise_files_uploads');
    });
