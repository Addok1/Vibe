<?php

use App\Http\Controllers\AgentCommissionController;
use App\Http\Controllers\AgentAddonsController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

    //agent-addons
    Route::group(['prefix' => 'agent-addons', 'middleware' => 'permission:agent_addons'], function () {
        Route::get('/', [AgentAddonsController::class, 'index'])->name('agentAddons.index');
        Route::post('/verfication-submit', [AgentAddonsController::class, 'verification_submit'])->name('agentAddons.verfication-submit');
        Route::post('/agent-files', [AgentAddonsController::class, 'agent_files_uploads'])->name('agentAddons.agent_files_uploads');
    });


    if (file_exists(app_path('Http/Controllers/AgentCommissionController.php'))) {
        Route::group(['prefix' => 'agents',  'middleware' => ['permission:view-agents', 'agent_addons']], function () {
            Route::get('/', [AgentCommissionController::class, 'agentView'])->name('agents.index');
            Route::middleware('remove_empty_query')->get('/list', [AgentCommissionController::class, 'agentlist'])->name('agents.list');
            Route::middleware(['permission:create-agents'])->get('/create', [AgentCommissionController::class, 'createAgent'])->name('agents.create');
            Route::post('/store', [AgentCommissionController::class, 'storeAgent'])->name('agents.store');
            Route::get('/edit/{id}', [AgentCommissionController::class, 'editAgent'])->name('agents.editAgent');
            Route::post('/update/{id}', [AgentCommissionController::class, 'Agentupdate'])->name('agents.update');
            Route::delete('/delete/{id}', [AgentCommissionController::class, 'destroyAgent'])->name('agents.destroy');
            Route::post('/update-status', [AgentCommissionController::class, 'updateStatus'])->name('agents.updateDocumentStatus');
            Route::middleware(['permission:edit-agent'])->get('/password/edit/{id}', [AgentCommissionController::class, 'editPassword'])->name('agents.password.edit');
            Route::post('/password/update/{id}', [AgentCommissionController::class, 'updatePasswords'])->name('agents.password.update');
            Route::middleware(['permission:agent-view-profile'])->get('/view-profile/{agent}', [AgentCommissionController::class, 'viewProfile'])->name('agents.viewProfile');

            Route::get('/request/list/{agent}', [AgentCommissionController::class, 'requestList'])->name('agents.requestList');
            Route::get('/payment-method/{agent}', [AgentCommissionController::class, 'listBankInfo'])->name('agents.listBankInfo');
            Route::get('/withdrawal-request/{agent}', [AgentCommissionController::class, 'agentWithdrawalRequest'])->name('agents.agentWithdrawalRequest');
            Route::get('/check-mobile/{mobile}', [AgentCommissionController::class, 'checkMobileExists']);
            Route::get('/check-mobile/{mobile}/{id}', [AgentCommissionController::class, 'checkMobileExists'])->name('agents.checkMobileExists');
        });


        Route::group(['prefix' => 'withdrawal-request-agents', 'middleware' => 'permission:withdrawal-request-agents'], function () {
            Route::get('/', [AgentCommissionController::class, 'WithdrawalRequestAgentsIndex'])->name('withdrawalrequestagents.index');
            Route::middleware('remove_empty_query')->get('/list', [AgentCommissionController::class, 'WithdrawalRequestAgentsList'])->name('withdrawalrequestagents.list');
            Route::get('/view-in-detail/{agents}', [AgentCommissionController::class, 'WithdrawalRequestAgentsViewDetails'])->name('withdrawalrequestagents.ViewDetails');
            //updatePaymentStatus
            Route::middleware('remove_empty_query')->get('/amounts/{agent_id}', [AgentCommissionController::class, 'WithdrawalRequestAmountAgents'])->name('WithdrawalRequestAmountAgents.list');
            Route::post('/update-status', [AgentCommissionController::class, 'updateAgentPaymentStatus'])->name('withdrawalrequestAgents.updateStatus');
        });
    }
});
