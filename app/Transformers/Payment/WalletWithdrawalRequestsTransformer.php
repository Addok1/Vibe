<?php

namespace App\Transformers\Payment;

use App\Transformers\Transformer;
use App\Models\Payment\WalletWithdrawalRequest;
use App\Base\Constants\Masters\WithdrawalRequestStatus;

class WalletWithdrawalRequestsTransformer extends Transformer
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected array $availableIncludes = [

    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(WalletWithdrawalRequest $wallet_history)
    {
        $params = [
            'id' => $wallet_history->id,
            'requested_amount' => $wallet_history->requested_amount,
            'requested_currency' => $wallet_history->requested_currency,
            'driver_id'=>$wallet_history->driver_id,
            'owner_id'=>$wallet_history->owner_id,
            'driver_name'=>$wallet_history->driverDetail ? $wallet_history->driverDetail->name : "",
            'driver_mobile'=>$wallet_history->driverDetail ? $wallet_history->driverDetail->mobile : "",
            'owner_name'=>$wallet_history->ownerDetail ? $wallet_history->ownerDetail->name : "",
            'owner_mobile'=>$wallet_history->ownerDetail ? $wallet_history->ownerDetail->mobile : "",
            'created_at' => $wallet_history->converted_created_at,
            'updated_at' => $wallet_history->converted_updated_at,
            'payment_status' => $wallet_history->payment_status,
            'franchise_id'=>$wallet_history->franchise_id,
            'franchise_name'=>$wallet_history->FranchiseDetail ? $wallet_history->FranchiseDetail->name : "",
            'franchise_mobile'=>$wallet_history->FranchiseDetail ? $wallet_history->FranchiseDetail->mobile : "",
            'agent_id'=>$wallet_history->agent_id,
            'agent_name'=>$wallet_history->agentDetail ? $wallet_history->agentDetail->first_name : "",
            'agent_mobile'=>$wallet_history->agentDetail ? $wallet_history->agentDetail->mobile : "",
        ];

        if($wallet_history->status==WithdrawalRequestStatus::REQUESTED){
            $params['status'] = 'Requested';
        }

        if($wallet_history->status==WithdrawalRequestStatus::APPROVED){
            $params['status'] = 'Approved';
        }

        if($wallet_history->status==WithdrawalRequestStatus::DECLINED){
            $params['status'] = 'Declined';
        }

        return $params;
    }
}
