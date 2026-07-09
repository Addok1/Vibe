<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Models\Admin\UserNeededDocument;
use Kreait\Firebase\Contract\Database;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Admin\UserDocument;
use App\Transformers\User\UserNeededDocumentTransformer;
use App\Http\Requests\Driver\DriverDocumentUploadRequest;
use App\Http\Controllers\Api\V1\Driver\OnlineOfflineController;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Log; 
use App\Models\Admin\NotificationChannel;
use App\Jobs\Mails\SendAccountDisapprovedMailNotification;

/**
 * @group Driver Document Management
 * @authenticated
 *
 * APIs for DriverNeededDocument's
 */
class UserDocumentController extends OnlineOfflineController
{
    
    /**
    * ImageUploader instance.
    *
    * @var ImageUploaderContract
    */
    protected $imageUploader;

    protected $database;

    /**
     * DriverDocumentController constructor.
     *
     * @param ImageUploaderContract $imageUploader
     */
    public function __construct(ImageUploaderContract $imageUploader,Database $database)
    {
        $this->imageUploader = $imageUploader;
        $this->database = $database;

    }
    public function index() {
        
    Log::info('User Document Status');
        $uploaded_document = false;
        $user_id = auth()->user()->id;

        $userneededdocumentQuery  = UserNeededDocument::active()->get();

        if($userneededdocumentQuery->isEmpty())
        {
            return $this->throwCustomException("Configuration mis match from Admin");
        }

        $neededdocument =  fractal($userneededdocumentQuery, new UserNeededDocumentTransformer);


        $user_approved_docs = UserDocument::where('user_id', $user_id)->where('document_status',DriverDocumentStatus::UPLOADED_AND_APPROVED)->count();
        $user_pending_docs = UserDocument::where('user_id', $user_id)
                                ->whereIn('document_status',[
                                    DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL,
                                    DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL
                                ])->count();
        if(count($userneededdocumentQuery) !== $user_approved_docs) {
            if($user_pending_docs == count($userneededdocumentQuery) - $user_approved_docs){
                $uploaded_document = true;
            }
        }

    Log::info('User Document Status', [
        'approved' => $user_approved_docs,
        'pending' => $user_pending_docs
    ]);
        $formated_document = $this->formatResponseData($neededdocument);

        return response()->json(['success'=>true,"message"=>'success','enable_submit_button'=>$uploaded_document,'data'=>$formated_document['data']]);
    }


    public function uploadDocuments(DriverDocumentUploadRequest $request) {
        $user = auth()->user();
        
        $created_params = $request->only(['document_id','identify_number','expiry_date']);

        $enable_user_auto_approval = get_settings('enable_document_auto_approval');

        $created_params['document_status'] =DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL;

        $document_exists = $user->userDocumentDetail()->where('document_id', $request->document_id)->exists();

        if ($document_exists) {
            $created_params['document_status'] =DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL;
            $user->update(['approve' => 0]);
        }
        $user_id = $user->id;

        $created_params['user_id'] = $user_id;

        if ($uploadedFile = $this->getValidatedUpload('document', $request)) {
            $created_params['image'] = $this->imageUploader->file($uploadedFile)
                ->saveUserDocument($user_id);
        }
        if($request->has('back_image'))
        {
            if ($uploadedFile = $this->getValidatedUpload('back_image', $request)) {
                $created_params['back_image'] = $this->imageUploader->file($uploadedFile)
                    ->saveUserDocument($user_id);
            }
        }
        // Check if document exists
        $user_documents = UserDocument::where('user_id', $user_id)->where('document_id', $request->input('document_id'))->first();

        if ($user_documents) {
            UserDocument::where('user_id', $user_id)->where('document_id', $request->input('document_id'))->update($created_params);
        } else {
            UserDocument::create($created_params);
        }

        if($enable_user_auto_approval=="1"){

            // Retrieve all active needed documents for the user
            $userNeededDocsCount = UserNeededDocument::where('active', true)->count();

            // Count the documents uploaded by the user
            $userApprovedDocsCount = UserDocument::where('user_id', $user_id)
                ->where('document_status', DriverDocumentStatus::UPLOADED_AND_APPROVED)
                ->count();

            // Check if both counts match
            if ($userNeededDocsCount === $userUploadedDocsCount)
            {
            
                $user->update(['approve' => 1]);
                
                // Update Firebase database
                $this->database->getReference('users/user_' . $user->id)
                    ->update([
                        'approve' => 1,
                        'updated_at' => Database::SERVER_TIMESTAMP,
                    ]);
        


            
                $notification = DB::table('notification_channels')
                ->where('topics', 'User Account Approval') // Match the correct topic
                ->first();
                 Log::info('User Document Status', [
                    'notification' => $notification,
                    'notification->push_notification' => $notification->push_notification
                ]);
        


                    // send push notification 
                    if ($notification && $notification->push_notification == 1) {
                    // Determine the user's language or default to 'en'
                    
                    Log::info('userLang');
                    $userLang = $user->lang ?? 'en';
                    // dd($userLang);

                    // Fetch the translation based on user language or fall back to 'en'
                    $translation = DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', $userLang)
                        ->first();

                    // If no translation exists, fetch the default language (English)
                    if (!$translation) {
                        $translation = DB::table('notification_channels_translations')
                            ->where('notification_channel_id', $notification->id)
                            ->where('locale', 'en')
                            ->first();
                    }
        
                
                    $title =  $translation->push_title ?? $notification->push_title;
                    $body = strip_tags($translation->push_body ?? $notification->push_body);
                    Log::info('Before dispatch notification');
                    dispatch(new SendPushNotification($user->user, $title, $body));
                    Log::info('After dispatch notification');
                }
            }
        }
    
        return $this->respondSuccess();

    }
}