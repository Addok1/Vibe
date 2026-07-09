<?php

namespace App\Transformers;

use App\Models\Promotion\PromotionTemplate;
use League\Fractal\TransformerAbstract;

class PromotionTemplateTransformer extends TransformerAbstract
{
    public function transform(PromotionTemplate $template)
    {
        return [
            'subject'       => $template->subject,
            'preview_image' => $template->preview_image
            ? asset('storage/uploads/promotion/previews/' . $template->preview_image)
            : null,

            'from'          => optional($template->from)->format('Y-m-d H:i:s'),
            'to'            => optional($template->to)->format('Y-m-d H:i:s'),
            'time'          => (int) $template->time, // seconds
            'active'        => (bool) $template->active,
        ];
    }
}
