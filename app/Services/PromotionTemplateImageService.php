<?php

namespace App\Services;

use App\Models\Promotion\PromotionTemplate;
use Illuminate\Support\Facades\Storage;

class PromotionTemplateImageService
{
    public static function generate(PromotionTemplate $template)
    {
        $imagePath = "promotion/previews/template_{$template->id}.png";
        $output = storage_path("app/public/{$imagePath}");

        if (!is_dir(dirname($output))) {
            mkdir(dirname($output), 0755, true);
        }

        $html = escapeshellarg($template->html);

        exec("node html-to-image.js $html $output");

        $template->update([
            'preview_image' => $imagePath
        ]);

        return Storage::url($imagePath);
    }
}
