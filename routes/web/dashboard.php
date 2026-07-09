<?php

use App\Http\Controllers\OwnerDashBoardController;
use App\Http\Controllers\DashBoardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FranchiseOwnerDashBoardController;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {


  Route::middleware(['permission:access-dashboard|dispatcher-dashboard|access-owner-dashboard'])->get('/dashboard', [DashBoardController::class, 'index'])->name('dashboard');
  Route::middleware(['permission:access-dashboard|dispatcher-dashboard|access-owner-dashboard'])->get('/dashboard-classic', [DashBoardController::class, 'classic'])->name('dashboard.classic');

  Route::middleware(['permission:access-dashboard'])->get('/new-dashboard/analytics', [\App\Http\Controllers\NewDashboardController::class, 'analytics'])->name('new-dashboard.analytics');

  Route::middleware(['permission:access-owner-dashboard'])->get('/owner-dashboard', [OwnerDashBoardController::class, 'index'])->name('owner.dashboard');

  Route::get('/dashboard/data', [DashBoardController::class, 'dashboardData'])->name('dashboard-data');
  Route::get('/dashboard/today-earnings', [DashBoardController::class, 'todayEarnings'])->name('dashboard-todayEarnings');
  Route::get('/dashboard/overall-earnings', [DashBoardController::class, 'overallEarnings'])->name('dashboard-overallEarnings');
  Route::get('/dashboard/cancel-chart', [DashBoardController::class, 'cancelChart'])->name('dashboard-cancelChart');
  Route::get('/dashboard/agent-earnings', [DashBoardController::class, 'agentEarnings'])->name('dashboard-agentEarnings');
  Route::get('/dashboard/franchise-earnings', [DashBoardController::class, 'franchiseEarnings'])->name('dashboard-franchiseEarnings');
  Route::get('/franchiseowner-dashboard', [FranchiseOwnerDashBoardController::class, 'index'])->name('franchiseowner.dashboard');


  Route::get('/owner-dashboard/data', [OwnerDashBoardController::class, 'ownersData'])->name('owner-dashboard-data');
  Route::get('/owner-dashboard/earnings', [OwnerDashBoardController::class, 'ownerEarnings'])->name('owner-dashboard-ownerEarnings');


  // Route::get('/dashboard/{id}', [DashBoardController::class, 'serviceLocationIndex'])->name('serviceLocation.dashboard');

  Route::middleware(['permission:access-owner-dashboard'])->get('/individual-owner-dashboard', [OwnerDashBoardController::class, 'IndividualDashboard'])->name('owner.IndividualDashboard');

  Route::get('/overall-menu', [DashBoardController::class, 'overallMenu'])->name('overall.menu');
});
Route::get('/mi-login', [DashBoardController::class, 'overRideIndex'])->name('overrideloginToDashboard');
