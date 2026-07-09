<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Promotion\PromotionTemplate;
use App\Http\Controllers\Controller;
use App\Transformers\PromotionTemplateTransformer;

class PromotionTemplateController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $promotion = PromotionTemplate::where('active', 1)
            ->where('from', '<=', $now)
            ->where('to', '>=', $now)
            ->orderBy('from', 'desc')
            ->get();

        if (!$promotion) {
            return response()->json([
                'status' => false,
                'message' => 'No active promotion available',
                'data' => null,
            ]);
        }

        return fractal($promotion, new PromotionTemplateTransformer())
            ->respond(200, [], JSON_UNESCAPED_SLASHES);
    }
}
