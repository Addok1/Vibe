<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Admin\ServiceLocation;
use App\Models\Admin\Franchise;
use App\Models\Country;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Models\Request\Request as RequestRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Admin\FranchiseOwnerNeededDocument;
use Carbon\Carbon;
use App\Base\Constants\Auth\Role;
use App\Models\Admin\FranchiseOwnerDocument;
use App\Base\Constants\Masters\DriverDocumentStatus;
use App\Models\Admin\Driver;
use App\Models\Request\RequestBill;
use App\Models\Admin\FranchiseDetail;
use App\Models\Payment\WalletWithdrawalRequest;
use App\Base\Filters\Admin\FranchiseFilter;
use Kreait\Firebase\Contract\Database;
use App\Transformers\Payment\WalletWithdrawalRequestsTransformer;
use App\Jobs\Mails\SendDriverWithdrawalAcceptMailNotification;
use App\Jobs\Mails\SendDriverWithdrawalDeclineMailNotification;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Method;
use App\Base\Filters\Admin\DriverFilter;
use App\Models\Payment\FranchiseWallet;
use App\Base\Constants\Masters\WalletRemarks;
use App\Base\Constants\Masters\WithdrawalRequestStatus;
use App\Models\Payment\FranchiseWalletHistory;
use App\Transformers\Payment\FranchiseWalletHistoryTransformer;
use App\Models\Admin\Zone;



class ManageFranchiseOwnerController extends Controller
{
    protected $imageUploader;
    protected $user;
    protected $franchiseowner;
    protected $database;

    public function __construct(ImageUploaderContract $imageUploader, User $user,Database $database)
    {
        $this->imageUploader = $imageUploader;
        $this->user = $user;   
        $this->database = $database;
    }
    public function index()
    {
        return Inertia::render('pages/manage-franchise-owner/index');
    }

   public function list(QueryFilterContract $queryFilter, Request $request)
    {
        $query = Franchise::query();
        $results = $queryFilter->builder($query)->paginate();

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }

    public function create(){
        

        $usedLocations = Franchise::pluck('zone_id');
        $zone = Zone::where('active', true)->whereNotIn('id', $usedLocations) ->get();

        $countries = Country::active()->get();

        return Inertia::render('pages/manage-franchise-owner/create',
            [   'zone'=> $zone , 
                'countries' => $countries,
            ]
        );
    }

    public function store(Request $request){

       $created_params =   $request->validate([
            'name' => 'required',
            'mobile' => 'required',
            'email' => 'required',
            'country' =>'required',
            'city' => 'required',
        ]);
         $created_params['password'] = bcrypt($request->input('password'));
        // dd($created_params);
        $created_params['created_by'] = auth()->user()->id;
        

        $created_params['status'] = true;

        if ($request->input('zone_id')) {
            $created_params['zone_id'] = $request->zone_id;
            $zone = Zone::where('id', $request->input('zone_id'))->first();
            $timezone = $zone->serviceLocation()->pluck('timezone')->first();
        } else {
            $timezone = config('app.timezone');
        }

        $user_params = ['name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'city'=>$request->input('city'),
            'country'=>$request->input('country'),
            'timezone'=>$timezone,
            'password' => bcrypt($request->input('password'))
        ];
        $user_params['password'] = bcrypt($request->input('password'));

        $user = $this->user->create($user_params);
      
        if ($uploadedFile = $request->file('profile_picture')) {
            $user['profile_picture'] = $this->imageUploader->file($uploadedFile)
                ->saveProfilePicture();
            $user->save();
        }

        $user->attachRole('franchise_owner');


        $user->franchise()->create($created_params);
        // dd($created_params);
        $user->franchise->franchiseWallet()->create(['amount_added'=>0]);
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Franchise created successfully.',
        ], 201);
    }

    public function edit(Franchise $franchise)
    {
         
        $usedLocations = Franchise::where('id', '!=', $franchise->id)->pluck('zone_id');

        $zone = Zone::where('active', true) ->whereNotIn('id', $usedLocations) ->get();


        $countries = Country::active()->get();

        return Inertia::render('pages/manage-franchise-owner/create',[ 'zone'=> $zone,
        'franchise' => $franchise,
        'countries' => $countries,
        'app_for'=>env('APP_FOR'),
        ]);
    }

    public function update(Request $request, Franchise $franchise){


        $updatedParams =  $request->validate([
            'name'=>'required',
            'mobile' => 'required',
            'email' => 'required',
            'city' => 'required',
            'country' =>'required',
          
        ]);
            
       
        if ($request->input('zone_id')) {
            $created_params['zone_id'] = $request->zone_id;
            $zone = Zone::where('id', $request->input('zone_id'))->first();
            $timezone = $zone->serviceLocation()->pluck('timezone')->first();
        } else {
            $timezone = config('app.timezone');
        }
        
        $user_params = ['name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'city'=>$request->input('city'),
            'country' =>$request->input('country'),
            'timezone'=>$timezone,       
        ];


        if ($uploadedFile = $request->file('profile_picture')) {
            $user_params['profile_picture'] = $this->imageUploader->file($uploadedFile)
                ->saveProfilePicture();
        }


        $franchise->user->update($user_params);

        $franchise->update($updatedParams);
        // dd($franchise);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Franchise updated successfully.',
        ], 201);

    }

     public function destroy(Franchise $franchise)
    {
        $franchise->user->delete();

        $franchise->delete();

        return response()->json([
            'successMessage' => 'Franchise deleted successfully',
        ]);
    }  
    public function updateStatus(Request $request)
    {
        $neededDoc = FranchiseOwnerNeededDocument::active()->count();

        $franchise = Franchise::with('franchiseOwnerDocument')
            ->findOrFail($request->id);

        $uploadedDoc = $franchise->franchiseOwnerDocument()
            ->where('document_status', 1)
            ->count();

        $status = $request->status ? 1 : 0;

        if ($status === 1 && ($neededDoc == 0 || $neededDoc != $uploadedDoc)) {
            return response()->json([
                'status'  => 'failure',
                'message' => 'Please upload and approve all required documents.',
                'data'    => 'uploaddocument'
            ]);
        }

        // Update franchise
        $franchise->update([
            'approve' => $status,
            'reason'  => null
        ]);

        // Firebase sync
        $this->database->getReference('franchise_owners/owner_' . $franchise->id)
            ->update([
                'approve'    => $status,
                'updated_at' => Database::SERVER_TIMESTAMP
            ]);

        $notification = \DB::table('notification_channels')
            ->where('topics', 'Driver Account Approval')
            ->first();

        //    send push notification 
        if ($notification && $notification->push_notification == 1) {
                // Determine the user's language or default to 'en'
            $userLang = $franchise->user->lang ?? 'en';
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
            dispatch(new SendPushNotification($franchise->user, $title, $body));
        }

        return response()->json([
            'successMessage' => 'Franchise Status updated successfully',
        ]);
    }

    public function editPassword(Franchise $franchise)
    {

        $serviceLocations = ServiceLocation::active()->get();
        $countries = Country::active()->get();


        return Inertia::render('pages/manage-franchise-owner/edit',
        [
            'serviceLocations'=> $serviceLocations,
            'franchise'=> $franchise,
            'countries'=> $countries,
            'app_for'=>env("APP_FOR"),
          
        ]);
    }

    public function updatePasswords(Request $request, Franchise $franchise)
    {
        // Validate the password and confirmation
        $updatedParams = $request->validate([
            'password' => 'required|min:8',  // Confirmed is for password_confirmation
            'confirm_password' => 'required|same:password',
        ]);
        
        $user_params = [
            'password'=>$request->input('password'),
            'confirm_password'=>$request->input('confirm_password'),         
        ];

        if($request->input('password')){
            $updatedParams['password'] = bcrypt($request->input('password'));
        }
        if($request->input('password')){
            $user_params['password'] = bcrypt($request->input('password'));
        }

        $franchise->user->update($user_params);

        $franchise->update($updatedParams);
        // dd($franchise);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Franchise password updated successfully.',
        ], 201);
    }

    public function viewProfile(Franchise $franchise) 
    {
        $completed_ride_count = RequestRequest::whereHas('driverDetail', function ($query) use ($franchise) {
            $query->where('franchise_owner_id', $franchise->id);
        })->where('is_completed', 1)->count();

        $canceled_ride_count = RequestRequest::whereHas('driverDetail', function ($query) use ($franchise) {
            $query->where('franchise_owner_id', $franchise->id);
        })->where('is_cancelled', 1)->count();
        

        $firebaseSettings = [
            'firebase_api_key' => get_firebase_settings('firebase_api_key'),
            'firebase_auth_domain' => get_firebase_settings('firebase_auth_domain'),
            'firebase_database_url' => get_firebase_settings('firebase_database_url'),
            'firebase_project_id' => get_firebase_settings('firebase_project_id'),
            'firebase_storage_bucket' => get_firebase_settings('firebase_storage_bucket'),
            'firebase_messaging_sender_id' => get_firebase_settings('firebase_messaging_sender_id'),
            'firebase_app_id' => get_firebase_settings('firebase_app_id'),
        ];

        $currency_code = get_settings('currency_code');
        $currency_symbol = get_settings('currency_symbol');

        $owner_wallet = $franchise->franchiseWallet;
        $total_admins = FranchiseDetail::where('created_by', $franchise->user_id)->count();

        $total_drivers = Driver::selectRaw('
                        IFNULL(SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END),0) AS approved,
                        IFNULL((SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS approve_percentage,
                        IFNULL((SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS decline_percentage,
                        IFNULL(SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END),0) AS declined,
                        count(*) AS total
                    ')
                ->whereHas('user', function ($query) {
                    $query->companyKey();
                });

        $total_drivers = $total_drivers->where('franchise_owner_id',$franchise->id)->first();

        $today = date('Y-m-d');
            // card Datas 
            
            $driver_ids = Driver::where('franchise_owner_id', $franchise->id)->get(); 
            $fleet_ids = FranchiseDetail::where('created_by',$franchise->user->id)->get();
        
            $fire_base_driver_ids = Driver::where('franchise_owner_id', $franchise->id)
            ->pluck('id')
            ->map(function ($id) {
                return 'driver_' . $id;
            });
        // dd($fire_base_driver_ids);

            //Today Earnings && today trips
            $cardEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=0,request_bills.total_amount,0)),0)";
            $cashEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=1,request_bills.total_amount,0)),0)";
            $walletEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=2,request_bills.total_amount,0)),0)";
            $adminCommissionQuery = "IFNULL(SUM(request_bills.admin_commision_with_tax),0)";
            $driverCommissionQuery = "IFNULL(SUM(request_bills.driver_commision),0)";
            $totalEarningsQuery = "$cardEarningsQuery + $cashEarningsQuery + $walletEarningsQuery";

            $todayEarnings = RequestRequest::leftJoin('request_bills', 'requests.id', '=', 'request_bills.request_id')
                            ->selectRaw("
                                {$cardEarningsQuery} AS card,
                                {$cashEarningsQuery} AS cash,
                                {$walletEarningsQuery} AS wallet,
                                {$totalEarningsQuery} AS total,
                                {$adminCommissionQuery} AS admin_commision,
                                {$driverCommissionQuery} AS driver_commision
                            ")
                            ->companyKey()
                            ->where('owner_id', $franchise->id)
                            ->where('requests.is_completed', true)
                            ->whereDate('requests.trip_start_time', date('Y-m-d'))
                            ->first();


            $todayTrips = RequestRequest::companyKey()
                                        ->whereDate('created_at', $today)
                                        ->where('owner_id', $franchise->id)
                                        ->selectRaw('
                                            IFNULL(SUM(CASE WHEN is_completed=1 THEN 1 ELSE 0 END), 0) AS today_completed,
                                            IFNULL(SUM(CASE WHEN is_completed=0 AND is_cancelled=0 THEN 1 ELSE 0 END), 0) AS today_scheduled,
                                            IFNULL(SUM(CASE WHEN is_cancelled=1 THEN 1 ELSE 0 END), 0) AS today_cancelled
                                        ')
                                        ->first();        


            //Over All Earnings
            $overallEarnings = RequestRequest::leftJoin('request_bills','requests.id','request_bills.request_id')
                                ->selectRaw("
                                {$cardEarningsQuery} AS card,
                                {$cashEarningsQuery} AS cash,
                                {$walletEarningsQuery} AS wallet,
                                {$totalEarningsQuery} AS total,
                                {$adminCommissionQuery} as admin_commision,
                                {$driverCommissionQuery} as driver_commision")
                                ->companyKey()
                                ->where('requests.owner_id', $franchise->id)
                                ->where('requests.is_completed',true)
                                ->first();


                                $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
                                $endDate = Carbon::now();
                                $earningsData=[];

                $months = [];
                $a = [];
                $u = [];
                $d = [];                          
                while ($startDate->lte($endDate))
                {
                    $from = Carbon::parse($startDate)->startOfMonth();
                    $to = Carbon::parse($startDate)->endOfMonth();
                    $shortName = $startDate->shortEnglishMonth;
                    $monthName = $startDate->monthName;
                
                    // Collect cancel data directly into arrays
                    $months[] = $shortName;
                    $a[] = RequestRequest::where('owner_id', $franchise->id)->whereBetween('created_at', [$from, $to])->where('cancel_method', '0')->whereIsCancelled(true)->count();
                    $u[] = RequestRequest::where('owner_id', $franchise->id)->whereBetween('created_at', [$from, $to])->where('cancel_method', '1')->whereIsCancelled(true)->count();
                    $d[] = RequestRequest::where('owner_id', $franchise->id)->whereBetween('created_at', [$from, $to])->where('cancel_method', '2')->whereIsCancelled(true)->count();
                
                    $earningsData['earnings']['months'][] = $monthName;
                    $earningsData['earnings']['values'][] = RequestBill::whereHas('requestDetail', function ($query) use ($from,$to, $franchise) {
                                        $query->where('owner_id', $franchise->id)->whereBetween('trip_start_time', [$from,$to])->whereIsCompleted(true);
                                    })->sum('total_amount');

                    $startDate->addMonth();
                }
            $currency_code = get_settings('currency_code');
            $currency_symbol = get_settings('currency_symbol');

        if(get_map_settings('map_type') == "open_street_map"){
            return Inertia::render('pages/manage-franchise-owner/open_view_profile',[
                'owner'=>$franchise, 
                'app_for'=>env("APP_FOR"),
                'default_lat'=>get_settings('default_latitude'),
                'default_lng'=>get_settings('default_longitude'),
                'owner_wallet'=>$owner_wallet,
                'firebaseSettings'=>$firebaseSettings,
                // 'fleetsEarnings' => $fleetsEarnings,
                'total_fleets'=> $total_admins,
                'total_drivers'=> $total_drivers,
                'driverIds' => $driver_ids,
                'earningsData' => $earningsData,
                'todayTrips' => $todayTrips,
                // 'fleetsEarnings' => $fleetsEarnings,
                'todayEarnings' => $todayEarnings,
                'overallEarnings' => $overallEarnings,
                // 'fleetDriverEarnings' => $fleetDriverEarnings,
                'currency_code' => $currency_code,
                'currencySymbol' => $currency_symbol,
                'completed_ride_count'=>$completed_ride_count,
                'canceled_ride_count'=>$canceled_ride_count,
                "fleet_ids" => $fleet_ids
            ]);

        }

        $map_key = get_map_settings('google_map_key');
        // dd($tripsChartData);

        return Inertia::render('pages/manage-franchise-owner/view_profile',
            [
                'owner'=>$franchise, 
                'app_for'=>env("APP_FOR"),
                'default_lat'=>get_settings('default_latitude'),
                'default_lng'=>get_settings('default_longitude'),
                'owner_wallet'=>$owner_wallet,
                'firebaseSettings'=>$firebaseSettings,
                'total_fleets'=> $total_admins,
                'total_drivers'=> $total_drivers,
                'driverIds' => $driver_ids,
                'earningsData' => $earningsData,
                'todayTrips' => $todayTrips,
                // 'fleetsEarnings' => $fleetsEarnings,
                'todayEarnings' => $todayEarnings,
                'overallEarnings' => $overallEarnings,
                // 'fleetDriverEarnings' => $fleetDriverEarnings,
                'map_key'=>$map_key,
                'currency_code' => $currency_code,
                'currencySymbol' => $currency_symbol,
                'completed_ride_count'=>$completed_ride_count,
                'canceled_ride_count'=>$canceled_ride_count,
                "fleet_ids" => $fleet_ids,
            ]);
    }
    public function employeeList(Franchise $franchise) {

        $query = FranchiseDetail::where('created_by', $franchise->user_id)->orderBy('created_at', 'desc') // Order by descending
        ->paginate();

        return response()->json([
            'query' => $query->items(),
            'paginator' => $query,
        ]); 
    }
     public function driverDocumentList(Franchise $franchise,QueryFilterContract $queryFilter) {

        // Fetch uploaded documents
        $ownerDocuments = $franchise->franchiseOwnerDocument ?: collect(); // Default to empty collection if null
        $ownerDocuments = $ownerDocuments->keyBy('document_id'); // Key by document_id for easy lookup
    
        // Fetch required documents
        $ownerNeededDocuments = FranchiseOwnerNeededDocument::where('active', true)->get();
    
        // Merge data

        $documents = $ownerNeededDocuments->map(function ($doc) use ($ownerDocuments) {
            $uploadedDoc = $ownerDocuments->get($doc->id);
            return [
                'id' => $doc->id,
                'name' => $doc->name,
                'doc_type' => $doc->doc_type,
                'has_identify_number' => $doc->has_identify_number,
                'has_expiry_date' => $doc->has_expiry_date,
                'active' => $doc->active,
                'identify_number_locale_key' => $doc->identify_number_locale_key,
                'account_type' => $doc->account_type,
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

    public function checkMobileExists(Request $request)
    {
        $query = Franchise::where('mobile', $request->mobile);
        if ($request->franchiseowner_id !== null) {
            $query->where('id', '!=', $request->owner_id);
        }
        $driverExists = $query->exists();
        return response()->json(['exists' => $driverExists]);
    }

    public function checkEmailExists(Request $request)
    {
        $query = Franchise::where('email', $request->email);
        if ($request->owner_id !== null) {
            $query->where('id', '!=', $request->owner_id);
        }
        $driverExists = $query->exists();
        return response()->json(['exists' => $driverExists]);
    }

    public function document(Franchise $franchiseowner) 
    {

        // Fetch uploaded documents
        $franchiseOwnerDocuments = $franchiseowner->franchiseOwnerDocument ?: collect(); // Default to empty collection if null
        $franchiseOwnerDocuments = $franchiseOwnerDocuments->keyBy('document_id'); // Key by document_id for easy lookup
//   dd($franchiseOwnerDocuments);  
        // Fetch required documents
        $franchiseOwnerNeededDocuments = FranchiseOwnerNeededDocument::where('active', true)->get();
    
        // Merge data
        $documents = $franchiseOwnerNeededDocuments->map(function ($doc) use ($franchiseOwnerDocuments) {
            $uploadedDoc = $franchiseOwnerDocuments->get($doc->id);
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
            ];
        });

        // dd($documents);
    
        return Inertia::render('pages/manage-franchise-owner/document', [
            'documents' => $documents,
            'franchiseownerId' => $franchiseowner->id,
        ]);

    }
    public function documentUpload(FranchiseOwnerNeededDocument $document, Franchise $franchiseownerId)
    {
        $uploaded = $franchiseownerId->franchiseOwnerDocument()->where('document_id', $document->id)->first();

// dd($document);
    return Inertia::render('pages/manage-franchise-owner/document_upload',['franchiseownerId'=>$franchiseownerId,
    'uploaded'=>$uploaded, 'document'=>$document,]);

    }
    public function documentUploadStore(Request $request, FranchiseOwnerNeededDocument $document, Franchise $franchiseownerId,)
    {

        // dd($request->all());
        $created_params = $request->only(['identify_number']);

        $created_params['owner_id'] = $franchiseownerId->id;
        $created_params['document_id'] = $document->id;

        $created_params['expiry_date'] = null;


        if($request->expiry_date!=null)
        {
            $expiry_date = Carbon::parse($request->expiry_date)->toDateTimeString();

            $created_params['expiry_date'] = $expiry_date;
        }


        if ($uploadedFile = $request->file('image')) {
            $created_params['image'] = $this->imageUploader->file($uploadedFile)
                ->saveFranchiseOwnerDocument($franchiseownerId->id);
        }

        if ($uploadedFile = $request->file('back_image')) {
            $created_params['back_image'] = $this->imageUploader->file($uploadedFile)
                ->saveFranchiseOwnerDocument($franchiseownerId->id);
        }
        // dd($created_params);

        // Check if document exists
        $owner_documents = FranchiseOwnerDocument::where('owner_id', $franchiseownerId->id)->where('document_id', $document->id)->first();

        if ($owner_documents) {
            $created_params['document_status'] = DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL;
            FranchiseOwnerDocument::where('owner_id', $franchiseownerId->id)->where('document_id', $document->id)->update($created_params);
        } else {
            $created_params['document_status'] = DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL;
            FranchiseOwnerDocument::create($created_params);
        }


        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Owner Document uploaded successfully.',
                'franchiseownerId'=>$franchiseownerId,
                'document'=>$document
                ], 201);

    }
    public function approvOwnerDocument($documentId,$franchiseownerId,$status)
    {
        $franchiseowner = Franchise::find($franchiseownerId);

        $ownerDoc = FranchiseOwnerDocument::where('owner_id', $franchiseownerId)->where('document_id', $documentId)->first();

        if (!$ownerDoc) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Document not found for the given driver.'
            ], 404); // Return a 404 status code for better semantics
        }

        $ownerDoc->update(['document_status' => $status]);


        $documentStatuses = $franchiseowner->franchiseOwnerDocument->pluck('document_status');
        // dd($documentStatuses);
        if($status==1)
        {
       
            $allDocumentsApproved = $documentStatuses->every(function ($value) {
                return $value == 1;
            });
            // dd($allDocumentsApproved);
            if ($allDocumentsApproved)
            {
                $franchiseowner->update(['approve'=>1]);
    
                $this->database->getReference('franchise_owners/owner_' . $franchiseowner->id)
                ->update(['approve' => 1, 'updated_at' => Database::SERVER_TIMESTAMP]);
        
                $title = custom_trans('driver_approved', [], $franchiseowner->user->lang);
                $body = custom_trans('driver_approved_body', [], $franchiseowner->user->lang);
            
                dispatch(new SendPushNotification($franchiseowner->user, $title, $body));
                return redirect()->route('manageowners.index');
           }
    
        }else{
            $allDocumentsDisapproved = $documentStatuses->every(function ($value) {
                return $value == 5;
            });
    
            if ($allDocumentsDisapproved){
                $franchiseowner->update(['approve'=>0]);
        
                $this->database->getReference('franchise_owners/owner_' . $franchiseowner->id)
                ->update(['approve' => 0, 'updated_at' => Database::SERVER_TIMESTAMP]);
        

                $title = custom_trans('driver_declined_title', [], $franchiseowner->user->lang);
                $body = custom_trans('driver_declined_body', [], $franchiseowner->user->lang);
            
                dispatch(new SendPushNotification($franchiseowner->user, $title, $body));  
                return redirect()->route('manageowners.index');
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Owner document approved successfully.'
        ]);

    }
    public function updateAndApprove(FranchiseOwner $franchiseownerId)
    {
        $documentStatuses = $franchiseownerId->franchiseOwnerDocument->pluck('document_status');

         // Handle the case where no document statuses exist
         if ($documentStatuses->isEmpty()) {           
            return response()->json(['message' => 'No documents found. Update not performed.']);
        }
       
        $allDocumentsApproved = $documentStatuses->every(function ($value) {
            return $value == 1;
        });
        // dd($allDocumentsApproved);
        if ($allDocumentsApproved)
        {
            $franchiseownerId->update(['approve'=>1]);

    
            $this->database->getReference('franchise_owners/owner_' . $franchiseownerId->id)
            ->update(['approve' => 1, 'updated_at' => Database::SERVER_TIMESTAMP]);
    

        $title = custom_trans('driver_approved', [], $franchiseownerId->user->lang);
        $body = custom_trans('driver_approved_body', [], $franchiseownerId->user->lang);
    
        dispatch(new SendPushNotification($franchiseownerId->user, $title, $body));


            return response()->json([
                'successMessage' => 'Owner  Approved successfully',
            ]);

        }else{
            // dd("Else ");

            return response()->json([
                'failureMessage' => 'Please Upload All Documents',
            ]);

        }
// dd($ownerId);

    }


    public function franchiseWithdrawalRequest(QueryFilterContract $queryFilter, Franchise $franchise)
    {

        $query = WalletWithdrawalRequest::where('franchise_id', $franchise->id)->whereHas('franchiseDetail.user',function($query){
            $query->companyKey();
            })->orderBy('created_at','desc')->with('franchiseDetail');

        $results =  $queryFilter->builder($query)->customFilter(new FranchiseFilter())->paginate();
        $items = fractal($results->items(), new WalletWithdrawalRequestsTransformer)->toArray();
        $results->setCollection(collect($items['data']));

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);  
    } 
    public function listBankInfo(Franchise $franchise){

        $franchise_wallet = $franchise->franchiseWallet;

            if (!$franchise_wallet) {

                $wallet_balance = 0;

            } else {                
                $wallet_balance = $franchise_wallet->amount_balance;

            }

        $methods = Method::with('fields')->get(); // Fetch all methods with their fields
        $bankInfos = $franchise->bankInfo;

        $formattedBankInfos = null;
        if($bankInfos != null){
            $formattedBankInfos = $methods->map(function ($method) use ($bankInfos) {
                $fields = $method->fields->map(function ($field) use ($bankInfos) {
                    $info = $bankInfos->firstWhere('field_id', $field->id);
        
                    return [
                        'field_name' => $field->input_field_name,
                        'value' => $info->value ?? null,
                    ];
                });
        
                return [
                    'method_name' => $method->method_name,
                    'fields' => $fields,
                ];
            });
        }

        return response()->json(['success' => true,
            'message' => 'wallet_history_listed',
            'wallet_balance' => $wallet_balance,
            'bank_info_exists' => $formattedBankInfos,
        ]);
    }

    public function WithdrawalRequestFranchiseIndex()
    {
        return Inertia::render('pages/withdrawal_request_franchise/index',['app_for'=>env("APP_FOR"),]);
    }
    public function WithdrawalRequestFranchiseList(QueryFilterContract $queryFilter)
    {

        $query = WalletWithdrawalRequest::whereHas('franchiseDetail.user',function($query){
            $query->companyKey();
            })->orderBy('created_at','desc')->with('franchiseDetail');

        $results =  $queryFilter->builder($query)->paginate();
        $items = fractal($results->items(), new WalletWithdrawalRequestsTransformer)->toArray();
        $results->setCollection(collect($items['data']));

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);  
    }   


    public function WithdrawalRequestFranchiseViewDetails(Franchise $franchise)
    {
        $walletBalance = $franchise->franchiseWallet ? $franchise->franchiseWallet->amount_balance : 0;
    
        $bankDetails = [
            'account_holder_name' => $franchise->name,
        ];
    
        $methods = Method::with('fields')->get(); // Fetch all methods with their fields
        $bankInfos = $franchise?->bankInfo ?? collect();

$formattedBankInfos = $methods->map(function ($method) use ($bankInfos) {

    $fields = $method->fields->map(function ($field) use ($bankInfos) {
        $info = $bankInfos->firstWhere('field_id', $field->id);

        return !empty($info?->value)
            ? [
                'field_name' => $field->input_field_name,
                'value'      => $info->value,
            ]
            : null;
    })->filter()->values(); // remove empty fields

    // Remove method if no fields have value
    if ($fields->isEmpty()) {
        return null;
    }

    return [
        'method_name' => $method->method_name,
        'fields'      => $fields,
    ];

})->filter()->values(); // 
    
        return Inertia::render('pages/withdrawal_request_franchise/view_in_details', [
            'app_for' => env("APP_FOR"),
            'walletBalance' => $walletBalance,
            'bankDetails' => $bankDetails,
            'franchise_id' => $franchise->id,
            'formattedBankInfos' => $formattedBankInfos,
        ]);
    }

    //WithdrawalRequestAmount 
    public function WithdrawalRequestAmountFranchise(QueryFilterContract $queryFilter, Franchise $franchise_id)
    {
        // Debugging driver_id for confirmation
    
        $query = WalletWithdrawalRequest::whereHas('franchiseDetail.user', function($query) {
            $query->companyKey();
        })
        ->where('franchise_id', $franchise_id->id) // Filter by driver_id
        ->orderBy('created_at', 'desc')
        ->with('franchiseDetail');
    
        $results = $queryFilter->builder($query)->customFilter(new DriverFilter())->paginate();
        $items = fractal($results->items(), new WalletWithdrawalRequestsTransformer)->toArray();
        $results->setCollection(collect($items['data']));
    
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }

    public function updateFranchisePaymentStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:wallet_withdrawal_requests,id',
            'status' => 'required|in:approved,declined',
        ]);
    
        $wallet_withdrawal_request = WalletWithdrawalRequest::findOrFail($request->id);
    
        if ($request->status === 'approved') {
            // Handle approval logic
            $franchise_wallet = FranchiseWallet::firstOrCreate(['user_id' => $wallet_withdrawal_request->franchise_id]);
            $franchise_wallet->amount_spent += $wallet_withdrawal_request->requested_amount;
            $franchise_wallet->amount_balance -= $wallet_withdrawal_request->requested_amount;
            $franchise_wallet->save();

             // Generate transaction_id
                 $transaction_id = str_random(6); 
    
            $wallet_withdrawal_request->franchiseDetail->franchiseWalletHistory()->create([
                'amount' => $wallet_withdrawal_request->requested_amount,
                'transaction_id' => $transaction_id,
                'remarks' => WalletRemarks::WITHDRAWN_FROM_WALLET,
                'is_credit' => false,
            ]);
    
            $wallet_withdrawal_request->status = 1; // Approved

            $user = $franchise_wallet->franchise->user;

            $currency = $user->countryDetail()->pluck('currency_symbol')->first();


            $notification = \DB::table('notification_channels')
            ->where('topics', 'Driver Withdrawal Request Approval') // Match the correct topic
            ->first();

            //   send push notification 
                if ($notification && $notification->push_notification == 1) {

                     // Determine the user's language or default to 'en'
                    $userLang = $user->lang ?? 'en';
    
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
                    $push_data = ['notification_enum'=>"payment_credited"];
                    dispatch(new SendPushNotification($user, $title, $body,$push_data));
                }

                //   send email account approved
                if (!empty($user->email)) {
                SendDriverWithdrawalAcceptMailNotification::dispatch($user, $transaction_id, $currency, $wallet_withdrawal_request, $franchise_wallet);
                }

          
           

        } elseif ($request->status === 'declined') {
            $wallet_withdrawal_request->status = 2; // Declined

            $franchise_wallet = FranchiseWallet::firstOrCreate(['user_id' => $wallet_withdrawal_request->franchise_id]);


            $user = $franchise_wallet->franchise->user;

            $notification = \DB::table('notification_channels')
            ->where('topics', 'Driver Withdrawal Request Decline') // Match the correct topic
            ->first();
            //   send push notification 
                if ($notification && $notification->push_notification == 1) {
                     // Determine the user's language or default to 'en'
                    $userLang = $user->lang ?? 'en';
                  
    
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
                        $push_data = ['notification_enum'=>"payment_declained"];
                    dispatch(new SendPushNotification($user, $title, $body,$push_data));
                }
                
                // send the mail withdrawal decline
                if (!empty($user->email)) {
                SendDriverWithdrawalDeclineMailNotification::dispatch($user);
                }
           
        }
    
        $wallet_withdrawal_request->payment_status = $request->status;
        $wallet_withdrawal_request->save();
    
        return response()->json([
            'successMessage' => 'Franchise payment status updated successfully.',
        ]);
    }
    public function walletAddAmount(Request $request, Franchise $franchise)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'operation' => 'required|in:add,subtract'
        ]);

        $owner_wallet = $franchise->franchiseWallet;
      
        if (!$owner_wallet) {
            // Create a new wallet for the driver
            $owner_wallet = $franchise->franchiseWallet()->create([
                // Add the necessary fields and their default values
                'amount_added' => 0, 
                'amount_balance' => 0, 
                'amount_spent' => 0, 
            ]);
        }

        $amount = $request->input('amount');
        $operation = $request->input('operation');
        $transaction_id = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        if ($operation === 'subtract' && $owner_wallet->amount_balance < $amount) {
            return response()->json(['message' => 'Insufficient funds'], 400);
        }


        if ($operation === 'add') {
            $owner_wallet->amount_added += $amount;
            $owner_wallet->amount_balance += $amount;
            $is_credit = true;
            $remarks = WalletRemarks::MONEY_DEPOSITED_TO_E_WALLET_FROM_ADMIN;
        } else {
            $owner_wallet->amount_balance -= $amount;
            $owner_wallet->amount_spent += $amount;
            $is_credit = false;
            $remarks = WalletRemarks::WITHDRAWN_FROM_WALLET;
        }

        $owner_wallet->save();

        FranchiseWalletHistory::create([
            'user_id' => $franchise->id,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'remarks' => $remarks,
            'is_credit' => $is_credit,
        ]);
        return response()->json(['message' => 'Amount adjusted successfully', 'transaction_id' => $transaction_id], 200);
    }
     public function walletHistoryList(Franchise $franchise)
    {

        // dd($driver);
        $results = $franchise->franchiseWalletHistory()->orderBy('created_at', 'desc')->paginate();        
        $items = fractal($results, new FranchiseWalletHistoryTransformer)->toArray();

        return response()->json([
            'results' => $items['data'],
            'paginator' => $results,
        ]);
    }
    public function wtihdrawalHistoryList(Franchise $franchise)
    {

        // dd($driver);
        $results = $franchise->withdrawalRequestsHistory()->orderBy('created_at', 'desc')->paginate();        
        $items = fractal($results, new WalletWithdrawalRequestsTransformer)->toArray();

        return response()->json([
            'results' => $items['data'],
            'paginator' => $results,
        ]);
    }
    public function driverList(Franchise $franchise) {

        $query = Driver::where('franchise_owner_id', $franchise->id)->orderBy('created_at', 'desc') // Order by descending
        ->paginate();

        return response()->json([
            'query' => $query->items(),
            'paginator' => $query,
        ]); 
    }
    
}
