<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    /** Cache key for settings key-value map (used in AppServiceProvider). */
    public const CACHE_KEY = 'app.settings';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * Clear settings cache (call when settings change).
     */
    public static function clearSettingsCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted()
    {
        static::saved(fn () => static::clearSettingsCache());
        static::deleted(fn () => static::clearSettingsCache());
    }

    /**
     * Get all settings as name => value, cached (1 hour). Invalidate on save/delete.
     *
     * @return array<string, mixed>
     */
    public static function getCached(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return static::pluck('value', 'name')->toArray();
        });
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'value',
        'option_value',
        'field',
        'group_name'
    ];

    public function getAppLogoAttribute(){
        if (!$this->value) {
            return null;
        }
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $this->value));
    }

    public function getFavIconAttribute(){
        if (!$this->value) {
            return null;
        }
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $this->value));
    }

    public function getLoginBgAttribute(){
        if (!$this->value) {
            return null;
        }
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $this->value));
    }

    public function uploadPath(){
        return config('base.system-admin.upload.logo.path');
    }
}
