<?php

namespace App\Models\Admin;

use Carbon\Carbon;
use App\Base\Uuid\UuidModel;
use App\Models\User;
use App\Models\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDocument extends Model
{
    use HasActive, UuidModel,SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'document_id', 
        'image',
        'identify_number',
        'expiry_date',
        'document_status',
        'comment',
        'back_image'  // Ensure back_image is fillable
    ];

    protected $appends = [
        'document_name',
        'identify_number_key'
    ];

    public $includes = [
        'userDetail'
    ];

    /**
     * Relationship: A document belongs to a user.
     */
    public function userDetail()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    /**
     * Accessor: Get the full file path for the front image.
     */
    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

         $relativePath = file_path($this->uploadPath(), $value);
            return url('storage/' . $relativePath);
    }

    /**
     * Accessor: Get the full file path for the back image.
     */
    public function getBackImageAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        // return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $value));

        $relativePath = file_path($this->uploadPath(), $value);
        return url('storage/' . $relativePath);
    }
    /**
    * Get the Document's name.
    *
    * @param string $value
    * @return string
    */
    public function getDocumentNameAttribute()
    {
        if (!$this->userNeededDocuments()->exists()) {
            return null;
        }
        return $this->userNeededDocuments->name;
    }
    /**
    * Get the is_identify_number_exists.
    *
    * @param string $value
    * @return string
    */
    public function getIdentifyNumberKeyAttribute()
    {
        if (!$this->userNeededDocuments()->exists()) {
            return null;
        }
        return $this->userNeededDocuments->identify_number_locale_key;
    }
    /**
    * The Document that the UserNeededDocuments belongs to.
    * @tested
    *
    * @return \Illuminate\Database\Eloquent\Relations\belongsTo
    */
    public function userNeededDocuments()
    {
        return $this->belongsTo(UserNeededDocument::class, 'document_id', 'id');
    }

    /**
     * The default file upload path.
     *
     * @return string|null
     */
    public function uploadPath()
    {
        if (!$this->userDetail()->exists()) {
            return null;
        }
        return folder_merge(config('base.user.upload.documents.path'), $this->userDetail->id);
    }
    /**
    * Get formated and converted timezone of user's created at.
    *
    * @param string $value
    * @return string
    */
    public function getConvertedCreatedAtAttribute()
    {
        if ($this->created_at==null||!auth()->user()) {
            return null;
        }
        if(auth()->user()){
            $timezone = auth()->user()->timezone?:config('app.timezone');
        }else{
            $timezone = config('app.timezone');
        }
        return Carbon::parse($this->created_at)->setTimezone($timezone)->format('jS M h:i A');
    }
    /**
    * Get formated and converted timezone of user's created at.
    *
    * @param string $value
    * @return string
    */
    public function getConvertedUpdatedAtAttribute()
    {
        if ($this->updated_at==null||!auth()->user()) {
            return null;
        }
       if(auth()->user()){
            $timezone = auth()->user()->timezone?:config('app.timezone');
        }else{
            $timezone = config('app.timezone');
        }
        return Carbon::parse($this->updated_at)->setTimezone($timezone)->format('jS M h:i A');
    }

    public function getExpiryDateAttribute($value)
    {
        if ($value==null) {
            return null;
        }
        // if(auth()->user()){
        //     $timezone = auth()->user()->timezone?:config('app.timezone');
        // }else{
        //     $timezone = config('app.timezone');
        // }

        $timezone = config('app.timezone');

        return Carbon::parse($value)->setTimezone($timezone)->format('Y-m-d');
    }
}
