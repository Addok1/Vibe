<?php

namespace App\Http\Controllers\Api\V1\Request;

use App\Models\Admin\Promo;
use App\Http\Controllers\Api\V1\BaseController;
use App\Transformers\Requests\PromoCodesTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group User-trips-apis
 *
 * APIs for User-trips apis
 */
class PromoCodeController extends BaseController
{
    protected $promocode;

    public function __construct(Promo $promocode)
    {
        $this->promocode = $promocode;
    }

    /**
    * List Promo codes for user
    * @responseFile responses/user/trips/promocode-list.json
    */
    public function index()
    {
        $zone_detail = find_zone(request()->input('pick_lat'), request()->input('pick_lng'));

        $current_date = Carbon::today()->toDateTimeString();

        $query = $this->promocode->where('from', '<=', $current_date)->where('to', '>=', $current_date)->get();//->where('service_location_id', $zone_detail->service_location_id)

    // Inject redeemed promo code into request
        $user = auth()->user();
        if ($user) {
            $redeemedPromo = cache()->get('active_promo_user_'.$user->id);
            if ($redeemedPromo) {
                request()->merge([
                    'coupon_code' => $redeemedPromo
                ]);
            }
        }


        $result = fractal($query, new PromoCodesTransformer);

        return $this->respondSuccess($result, 'promo_listed');
    }

     public function redeem(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string'
        ]);

        $user = auth()->user();

        // Store applied promo (1 min or configurable)
        cache()->put(
            'active_promo_user_'.$user->id,
            $request->promo_code,
            now()->addMinute()
        );

        return $this->respondSuccess([], 'promo_applied');
    }

    public function clear()
    {
        $user = auth()->user();

        if ($user) {
            cache()->forget('active_promo_user_'.$user->id);
        }

        return $this->respondSuccess([], 'promo_cleared');
    }
}
