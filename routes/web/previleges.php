<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {


    Route::group(['prefix' => 'roles', 'middleware' => 'permission:roles'], function () {
        Route::get('/', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/list', [RoleController::class, 'getRoles'])->name('roles.list');
        Route::post('/', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/{role}', [RoleController::class, 'destroy']);
    });

    Route::group(['prefix' => 'permissions', 'middleware' => 'permission:permissions'], function () {
        Route::get('/{permission}', [PermissionController::class, 'index'])->name('permission.index');
        Route::post('/{role}', [PermissionController::class, 'store'])->name('permission.store');
    });
});
