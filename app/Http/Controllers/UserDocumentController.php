<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\UserDocument;
use Kreait\Firebase\Contract\Database;
use App\Models\Admin\UserNeededDocument;
use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Jobs\Mails\SendUserAccountApprovedMailNotification;
use App\Jobs\Mails\SendUserAccountDisapprovedMailNotification;
use App\Jobs\Notifications\SendPushNotification;
use Illuminate\Support\Facades\Log; 


class UserDocumentController extends Controller
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

    public function userNeededDocumentIndex() {
        return Inertia::render('pages/user_needed_documents/index');
    }

    public function userNeededDocumentList(QueryFilterContract $queryFilter,) {
        $query = UserNeededDocument::query();
        $results = $queryFilter->builder($query)->paginate();
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }

    public function userNeededDocumentCreate() 
    {
        return Inertia::render('pages/user_needed_documents/create');
    }

    public function userNeededDocumentStore(Request $request) 
    {
        if(env('APP_FOR') == 'demo') {
            return response()->json([
                'alertMessage' => 'You are not Authorized',
            ],403);
        }
        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required',
            'has_identify_number' => 'required',
            'has_expiry_date' => 'required',
        ]);
        if($request->has_identify_number){
            $validated['identify_number_locale_key'] = $request->identify_number_locale_key;
        }

        $validated['image_type'] = $request->image_type;
        $validated['document_name_front'] = $request->document_name_front;
        $validated['document_name_back'] = $request->document_name_back;

        $validated['is_editable'] = $request->is_editable;
        $validated['is_required'] = $request->is_required;
        $validated['active'] = true;



        $document = UserNeededDocument::create($validated);
        return response()->json([
            'successMessage' => 'Document created successfully.',
            'result' => $document,
        ],201);

    }
    public function userNeededDocumentEdit(UserNeededDocument $document,Request $request) 
    {
        return Inertia::render('pages/user_needed_documents/create',['document'=>$document]);
    }
    public function userNeededDocumentUpdate(UserNeededDocument $document,Request $request) 
    {
        if(env('APP_FOR') == 'demo') {
            return response()->json([
                'alertMessage' => 'You are not Authorized',
            ],403);
        }
        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required',
            'has_identify_number' => 'required',
            'has_expiry_date' => 'required',
        ]);
        $validated['image_type'] = $request->image_type;
        $validated['document_name_front'] = $request->document_name_front;
        $validated['document_name_back'] = $request->document_name_back;

        $validated['is_editable'] = $request->is_editable;
        $validated['is_required'] = $request->is_required;


        if($request->has_identify_number){
            $validated['identify_number_locale_key'] = $request->identify_number_locale_key;
        }
        $document->update($validated);
        return response()->json([
            'successMessage' => 'Document Updated successfully.',
            'result' => $document,
        ],201);
    }
    public function userNeededDocumentToggle(Request $request) {
        if(env('APP_FOR') == 'demo') {
            return response()->json([
                'alertMessage' => 'You are not Authorized',
            ],403);
        }
        UserNeededDocument::where('id',$request->id)->update(['active'=>$request->status]);
        return response()->json([
            'successMessage' => 'Document Status updated successfully.',
        ],201);
    }
    public function userNeededDocumentDelete(UserNeededDocument $document) {
        if(env('APP_FOR') == 'demo') {
            return response()->json([
                'alertMessage' => 'You are not Authorized',
            ],403);
        }
        // dd($document);
        $document->delete();
        return response()->json([
            'successMessage' => 'Document Deleted successfully.',
        ],201);
    }


    public function userViewDocument(User $user)
    {
        // Fetch uploaded documents
        $userDocuments = $user->userDocumentDetail ?: collect(); // Default to empty collection if null
        $userDocuments = $userDocuments->keyBy('document_id'); // Key by document_id for easy lookup
    
        // Fetch required documents
        $userNeededDocuments = UserNeededDocument::where('active', true)->get();
    
        // Merge data
        $documents = $userNeededDocuments->map(function ($doc) use ($userDocuments) {
            $uploadedDoc = $userDocuments->get($doc->id);
            return [
                'id' => $doc->id,
                'name' => $doc->name,
                'doc_type' => $doc->doc_type,
                'has_identify_number' => $doc->has_identify_number,
                'has_expiry_date' => $doc->has_expiry_date,
                'active' => $doc->active,
                'identify_number_locale_key' => $doc->identify_number_locale_key,
                'uploaded' => $uploadedDoc ? true : false,
                'expiry_date' => $uploadedDoc->expiry_date ?? null,
                'identify_number' => $uploadedDoc->identify_number ?? null,
                'document_status' => $uploadedDoc->document_status ?? null,
                'comment' => $uploadedDoc->comment ?? null,
                'image' => $uploadedDoc->image ?? null,
                'back_image' => $uploadedDoc->back_image ?? null,
                'document_name_front' => $doc->document_name_front, // Include front name
                'document_name_back' => $doc->document_name_back, // Include back name
            ];
        });

        // dd($documents);
    
        return Inertia::render('pages/user/document', [
            'documents' => $documents,
            'userId' => $user->id,
        ]);
    }
    

    public function userDocumentList(User $user,QueryFilterContract $queryFilter) {
        
        // Fetch uploaded documents
        $userDocuments = $user->userDocumentDetail ?: collect(); // Default to empty collection if null
        $userDocuments = $userDocuments->keyBy('document_id'); // Key by document_id for easy lookup
    
        // Fetch required documents
        $userNeededDocuments = UserNeededDocument::where('active', true)
          ->get();

        $documents = $userNeededDocuments->map(function ($doc) use ($userDocuments) {
            $uploadedDoc = $userDocuments->get($doc->id);
            return [
                'id' => $doc->id,
                'name' => $doc->name,
                'doc_type' => $doc->doc_type,
                'has_identify_number' => $doc->has_identify_number,
                'has_expiry_date' => $doc->has_expiry_date,
                'active' => $doc->active,
                'identify_number_locale_key' => $doc->identify_number_locale_key,
                'uploaded' => $uploadedDoc ? true : false,
                'expiry_date' => $uploadedDoc->expiry_date ?? null,
                'identify_number' => $uploadedDoc->identify_number ?? null,
                'document_status' => $uploadedDoc->document_status ?? null,
                'comment' => $uploadedDoc->comment ?? null,
                'image' => $uploadedDoc->image ?? null,
                'back_image' => $uploadedDoc->back_image ?? null,
                'document_name_front' => $doc->document_name_front, // Include front name
                'document_name_back' => $doc->document_name_back, // Include back name
            ];
        });
        
        return response()->json([
            'results' => $documents,
        ]);
    }


    public function documentUpload(UserNeededDocument $document, User $userId)
    {
        $uploaded = $userId->userDocumentDetail()->where('document_id', $document->id)->first();

        return Inertia::render('pages/user/document_upload',['userId'=>$userId,
        'uploaded'=>$uploaded, 'document'=>$document,]);

    }
    public function documentUploadStore(Request $request, UserNeededDocument $document, User $userId,)
    {

        // dd($request->all());
        $created_params = $request->only(['identify_number']);

        $created_params['user_id'] = $userId->id;
        $created_params['document_id'] = $document->id;

        $created_params['expiry_date'] = null;


        if($request->expiry_date!=null)
        {
            $expiry_date = Carbon::parse($request->expiry_date)->toDateTimeString();

            $created_params['expiry_date'] = $expiry_date;
        }

        if ($uploadedFile = $request->file('image')) {
            $created_params['image'] = $this->imageUploader->file($uploadedFile)
                ->saveUserDocument($userId->id);
        }
        // if($request->hasFile('backImageFile'))
        // {
            if ($uploadedFile = $request->file('back_image')) {
                $created_params['back_image'] = $this->imageUploader->file($uploadedFile)
                    ->saveUserDocument($userId->id);
            }
        // }
        // dd($created_params);

        // Check if document exists
        $user_documents = UserDocument::where('user_id', $userId->id)->where('document_id', $document->id)->first();

        if ($user_documents) {
            $created_params['document_status'] = DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL;
            UserDocument::where('user_id', $userId->id)->where('document_id', $document->id)->update($created_params);
        } else {
            $created_params['document_status'] = DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL;
            UserDocument::create($created_params);
        }




        // Optionally, return a response
        return response()->json([
            'successMessage' => 'User Document uploaded successfully.',
                'userId'=>$userId,
                'document'=>$document
                ], 201);

    }

    public function approveDocumentStatus($user)
    {
        $neededDoc = UserNeededDocument::active()->count();
        $user = User::with('userDocumentDetail')->find($user);
        $uploadedDoc = $user->userDocumentDetail()
            ->whereHas('userNeededDocuments', function ($query) {
                $query->where('active', true);
            })
            ->whereIn('document_status', [1])
            ->count();

            if($neededDoc != $uploadedDoc){
                return response()->json([
                    'status' => 'failure',
                    'message' => 'User document Disapproved.',
                    'data' =>'uploaddocument'
                ]);            
            }       

        
        
            $user->update([
                'approve'=>1,
                'reason' =>Null
            ]);

            $this->database->getReference('users/user_' . $user->id)
            ->update(['approve' => 1, 'updated_at' => Database::SERVER_TIMESTAMP]);
    
            // $title = custom_trans('user_approved', [], $user->user->lang);
            // $body = custom_trans('user_approved_body', [], $user->user->lang);
        


            $notification = \DB::table('notification_channels')
            ->where('topics', 'User Account Approval') // Match the correct topic
            ->first();

            // send push notification 
            if ($notification && $notification->push_notification == 1) {
                // Determine the user's language or default to 'en'
                $userLang = $user->lang ?? 'en';
                // dd($userLang);

                // Fetch the translation based on user language or fall back to 'en'
                $translation = \DB::table('notification_channels_translations')
                    ->where('notification_channel_id', $notification->id)
                    ->where('locale', $userLang)
                    ->first();

                // If no translation exists, fetch the default language (English)
                if (!$translation) {
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', 'en')
                        ->first();
                }
        
                
                $title =  $translation->push_title ?? $notification->push_title;
                $body = strip_tags($translation->push_body ?? $notification->push_body);
                dispatch(new SendPushNotification($user, $title, $body));
            }

            if ($notification && $notification->mail == 1) {
                //   send email account approved
              if ($user->email !=null){
                SendUserAccountApprovedMailNotification::dispatch($user);
               }
                
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User document Approved.',
            ]);
            
    

    }

    public function approveUserDocument($documentId, $userId, $status, Request $request)
    {
        $userDoc = UserDocument::where('user_id', $userId)->where('document_id', $documentId)->first();
    
        if (!$userDoc) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Document not found for the given user.'
            ], 404); // Return a 404 status code for better semantics
        }
    
        $userDoc->update(['document_status' => $status]);
    
        $user = User::find($userId);

        if ($status == 5) {
            $user->update([
                'approve' => 0,
                'reason' => $request->reason,
            ]);
        }
        $documentStatuses = $user->userDocumentDetail->pluck('document_status');
        $neededDoc = UserNeededDocument::active()->where('is_required', true)->count();
        $uploadedDoc = $user->userDocumentDetail()->whereHas('userNeededDocuments',function($query) {
            $query->where('active',true)->where('is_required', true);
        })->count();

        // if( $neededDoc != $uploadedDoc){
        //     return redirect('approved-users/document/'.$user->id);
        // }
        if ($neededDoc != $uploadedDoc) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Required documents are missing.',
                'redirect' => 'approved-users/document/'.$user->id
            ], 422);
        }

        if($status==1)
        {

            // Get all document IDs that match the condition
            $docIds = $user->userDocumentDetail()
            ->whereHas('userNeededDocuments', function ($query) {
                $query->where('active', true)->where('is_required', true);
            })->pluck("id");

             // Get statuses of the driver's documents
            $documentStatus = $user->userDocumentDetail->whereIn('id', $docIds)->pluck('document_status');

            $allDocumentsApproved = $documentStatus->every(function ($value) {
                return $value == 1;
            });

            // // Get statuses of the user's documents
            // $user_count = $user->userDocumentDetail()->whereHas('userNeededDocuments',function($query) {
            //     $query->where('active',true)->where('is_required', true);
            // })->where('document_status',1)->count();

            // $allDocumentsApproved = $neededDoc === $user_count;
            if ($allDocumentsApproved)
            {
                $user->update([
                    'approve'=>1,
                    'reason' => null
                ]);
    
                $this->database->getReference('users/user_' . $user->id)
                ->update(['approve' => 1, 'updated_at' => Database::SERVER_TIMESTAMP]);
        
                // $title = custom_trans('user_approved', [], $user->user->lang);
                // $body = custom_trans('user_approved_body', [], $user->user->lang);
            


                $notification = \DB::table('notification_channels')
                ->where('topics', 'User Account Approval') // Match the correct topic
                ->first();

                // send push notification 
                if ($notification && $notification->push_notification == 1) {
                    // Determine the user's language or default to 'en'
                    $userLang = $user->lang ?? 'en';
                    // dd($userLang);

                    // Fetch the translation based on user language or fall back to 'en'
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', $userLang)
                        ->first();

                    // If no translation exists, fetch the default language (English)
                    if (!$translation) {
                        $translation = \DB::table('notification_channels_translations')
                            ->where('notification_channel_id', $notification->id)
                            ->where('locale', 'en')
                            ->first();
                    }
            
                    
                    $title =  $translation->push_title ?? $notification->push_title;
                    $body = strip_tags($translation->push_body ?? $notification->push_body);                    
                    $pushUser = User::find($user->id);
                    dispatch(new SendPushNotification($pushUser, $title, $body));
                }

                if ($notification && $notification->mail == 1) {
                    
                    $pushUser = User::find($user->id);
                    if ($user->email !=null){
                        SendUserAccountApprovedMailNotification::dispatch($pushUser);
                    }
                }
                // dispatch(new SendPushNotification($user->user, $title, $body));
                // return redirect()->route('approveduser.Index');
                return response()->json([
                    'status' => 'success',
                    'message' => 'User document approved successfully.',
                    'allDocumentsApproved'=>$allDocumentsApproved,
                ]);
           }
    
        }else{


         $allDocumentsDisapproved = $documentStatuses->contains(5);

            // // Get statuses of the user's documents
            // $user_count = $user->userDocumentDetail()->whereHas('userNeededDocuments',function($query) {
            //     $query->where('active',true)->where('is_required', true);
            // })->where('document_status',5)->count();

            // $allDocumentsDisapproved = $neededDoc === $user_count;
    
            if ($allDocumentsDisapproved)
            {
                $user->update(['approve'=>0]);
    
                $this->database->getReference('users/user_' . $user->id)
                ->update(['approve' => 0, 'updated_at' => Database::SERVER_TIMESTAMP]);
        
                // $title = custom_trans('user_declined_title', [], $user->user->lang);
                // $body = custom_trans('user_declined_body', [], $user->user->lang);

                
                $notification = \DB::table('notification_channels')
                ->where('topics', 'Driver Account Disapproval') // Match the correct topic
                ->first();
                
                // send push notification 
                if ($notification && $notification->push_notification == 1) {
                     // Determine the user's language or default to 'en'
                    $userLang = $user->lang ?? 'en';
                    // dd($userLang);
    
                    // Fetch the translation based on user language or fall back to 'en'
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', $userLang)
                        ->first();
    
                    // If no translation exists, fetch the default language (English)
                    if (!$translation) {
                        $translation = \DB::table('notification_channels_translations')
                            ->where('notification_channel_id', $notification->id)
                            ->where('locale', 'en')
                            ->first();
                    }
            
                    
                    $title =  $translation->push_title ?? $notification->push_title;
                    $body = strip_tags($translation->push_body ?? $notification->push_body);
                    $pushUser = User::find($user->id);
                    dispatch(new SendPushNotification($pushUser, $title, $body));
                }

                
                if ($notification && $notification->mail == 1) {
                    
                    $pushUser = User::find($user->id);
                    // send email account disapproval
                    if ($user->email !=null){
                        SendUserAccountDisapprovedMailNotification::dispatch($pushUser);
                    }
                }
                // dispatch(new SendPushNotification($user->user, $title, $body));  
                // return redirect()->route('approveduser.Index');
                return response()->json([
                    'status' => 'success',
                    'message' => 'User document Disapproved.',
                    'allDocumentsDisapproved'=>$allDocumentsDisapproved
                ]);
           }
        }


    
        return response()->json([
            'status' => 'success',
            'message' => 'User document updated successfully.',
        ]);
    }

    public function userApprovalToggle(Request $request)
    {
        $user = User::find($request->id);
        if(!$user){
            return response()->json([
                'alertMessage' => 'User not Found',
            ],403);
        }
        
        $status = false;

            // Get all document IDs that match the condition
        $needed_count = UserNeededDocument::where('active', true)->where('is_required', true)->count();

        // Get statuses of the user's documents
        $user_count = $user->userDocumentDetail()->where('document_status',1)->count();

        $allDocumentsApproved = $needed_count === $user_count;
        if($allDocumentsApproved){
            $status = true;
        }
        User::where('id', $request->id)->update(['approve'=> $status]);

        if($status){

            return response()->json([
                'successMessage' => 'User status updated successfully',
            ]);
        }else{
            return response()->json([
                'status' => 'failure',
                'message' => 'User document Disapproved.',
                'data' =>'uploaddocument'
            ]);
        }


    }
    
}