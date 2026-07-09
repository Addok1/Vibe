<?php

namespace App\Models\Admin;

use App\Base\Uuid\UuidModel;
use App\Models\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DeleteOldFiles;
use Illuminate\Support\Facades\Storage;
use App\Models\Master\MobileAppSetting;
use App\Models\Admin\Setting;

class BannerImageModel extends Model
{
    use HasActive, UuidModel,DeleteOldFiles;

    protected $table = 'bannersimages';

    /**
     * UUID primary key must be treated as string.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = ['title','bannertype','image','active','imageurl','appmodule_id','description','button_name','previewimage', 'enable_banner_button', 'banner_bg_color','banner_title_color','banner_description_color','banner_button_color','banner_button_text_color'];

    public $deletableFiles = [
        'image'
    ];

       public $sortable = [
        'banner_message',
    ];

    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $value));
        //  $relativePath = file_path($this->uploadPath(), $value);
        //     return url('storage/' . $relativePath);
        return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $value));
    }

    /**
     * The default file upload path.
     *
     * @return string|null
     */
    public function uploadPath()
    {
        return config('base.bannerimage.upload.images.path');
    }

    public function appmodule()
    {
        return $this->belongsTo(MobileAppSetting::class, 'appmodule_id','id');
    }
    public function setting()
    {
        return $this->belongsTo(Setting::class, 'setting_id','id');
    }
    public function getPreviewimageUrlAttribute()
    {
        if (!$this->previewimage) {
            return null;
        }

        return storage_public_url($this->previewimage, 'uploads/banner/banner-preview');
    }
}
