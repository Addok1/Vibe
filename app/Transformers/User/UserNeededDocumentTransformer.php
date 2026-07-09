<?php

namespace App\Transformers\User;

use App\Models\Admin\UserDocument;
use App\Models\Admin\UserNeededDocument;
use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Transformers\User\UserDocumentTransformer;
use App\Base\Constants\Masters\DriverDocumentStatusString;
use App\Transformers\Transformer;

class UserNeededDocumentTransformer extends Transformer
{
    /**
    * Resources that can be included if requested.
    *
    * @var array
    */
    protected array $availableIncludes = [
        'driver_document',
    ];
    /**
     * Resources that can be included default.
     *
     * @var array
     */
    protected array $defaultIncludes = [
        'driver_document',
    ];
    /**
     * A Fractal transformer.
     *
     * @param UserNeededDocument $userneededdocument
     * @return array
     */
    public function transform(UserNeededDocument $userneededdocument)
    {
        $params =  [
            'id'=>$userneededdocument->id,
            'name' => $userneededdocument->name,
            'doc_type' => $userneededdocument->doc_type,
            'has_identify_number' => (bool)$userneededdocument->has_identify_number,
            'has_expiry_date' => (bool) $userneededdocument->has_expiry_date,
            'active' => $userneededdocument->active,
            'identify_number_locale_key'=>$userneededdocument->identify_number_locale_key,
            'is_uploaded'=>false,
            'document_status'=>2,
            'is_editable' => $userneededdocument->is_editable == 1,
            'document_status_string'=>DriverDocumentStatusString::NOT_UPLOADED
        ];

        $user_document = UserDocument::where('document_id', $userneededdocument->id)->where('user_id', auth()->user()->id)->first();


        $params['is_front_and_back'] = false;


        if($userneededdocument->image_type=='front_and_back')
        {
            $params['is_front_and_back'] = true;
            $params['document_name_front'] = $userneededdocument->document_name_front;
            $params['document_name_back'] = $userneededdocument->document_name_back;

        }

        if ($user_document) {
            $params['is_uploaded'] = true;
            $params['document_status']= $user_document->document_status;

            foreach (DriverDocumentStatus::DocumentStatus() as $key=> $document_string) {
                if ($user_document->document_status==$key) {
                    $params['document_status_string'] = $document_string;
                }
            }
        }

        return $params;
    }

    /**
     * Include the user document of the user needed document.
     *
     * @param UserNeededDocument $userneededdocument
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeDriverDocument(UserNeededDocument $userneededdocument)
    {
        $document = $userneededdocument->userDocument()->where('user_id', auth()->user()->id)->first();

        return $document
        ? $this->item($document, new UserDocumentTransformer)
        : $this->null();
    }
}
