<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Request\Request;
use App\Models\User;
use App\Models\Admin\Subscription;

class PaymentRequest extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';


    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','user_id', 'amount', 'payment_for', 'status','request_id','currency','is_paid','plan_id','pawapay_deposit_id'
    ];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [

    ];

        /**
    * The accessors to append to the model's array form.
    *
    * @var array
    */
    protected $appends = [
        'converted_created_at','user_name','request_number','plan_name',

    ];
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
        $timezone = auth()->user()->timezone?:config('app.timezone');
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
        $timezone = auth()->user()->timezone?:config('app.timezone');
        return Carbon::parse($this->updated_at)->setTimezone($timezone)->format('jS M h:i A');
    }

    public function userDetail()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function requestDetail()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    }

    public function planDetail()
    {
        return $this->belongsTo(Subscription::class, 'plan_id', 'id');
    }

    public function getUserNameAttribute()
    {
        if ($this->userDetail==null) {
            return null;
        }
        return User::where('id',$this->user_id)->first()->name;
    }

    public function getRequestNumberAttribute()
    {
        if ($this->requestDetail==null) {
            return null;
        }
        return Request::where('id',$this->request_id)->first()->request_number;
    }

    public function getPlanNameAttribute()
    {
        if ($this->planDetail==null) {
            return null;
        }
        return Subscription::where('id',$this->plan_id)->first()->name;
    }
}
