<?php

namespace App\Models\Admin;

use App\Models\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Models\Admin\UserDocument;

class UserNeededDocument extends Model
{
    use HasActive,SearchableTrait;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_needed_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'doc_type', 'has_identify_number','has_expiry_date','active',
        'identify_number_locale_key','document_name_front','document_name_back',
        'image_type','is_editable','is_required'
    ];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [
        'userDocument'
    ];

        /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'user_needed_documents.name' => 20,
        ],


    ];

    /**
     * The Driver Document associated with the user needed document's id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function userDocument()
    {
        return $this->hasOne(UserDocument::class, 'document_id', 'id');
    }
}
