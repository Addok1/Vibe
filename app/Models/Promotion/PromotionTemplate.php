<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Model;

class PromotionTemplate extends Model
{
    protected $table = 'promotion_templates';

    protected $fillable = ['subject', 'html','preview_image','from', 'to', 'time', 'active'];

    protected $casts = [
        'from' => 'datetime',
        'to'   => 'datetime',
        'active' => 'boolean',
    ];
}
