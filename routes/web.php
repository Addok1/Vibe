<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Models\Setting;
use Inertia\Inertia;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
 * These routes use the root namespace 'App\Http\Controllers\Web'.
 */
Route::namespace('Web')->group(function () {

    // All the folder based web routes
    include_route_files('web');

});

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {

   
    Route::post('/set-locale', function (Request $request) {session(['selectedLocale' => $request->locale]);
        return response()->json(['status' => 'success']);
    });


});
    

    Route::middleware('guest')->get('/mi-admin',  [LoginController::class,'adminLogin'])->name('login.admin');

    Route::group(['prefix'=>'login','middleware'=>'redirect_dynamic_login'],function(){

        // GET /login: middleware redirects to login/user (customer login)
        Route::get('/', function () {
            $segment = trim((string) (Setting::where('name', 'user_login')->value('value') ?? '')) ?: 'user';
            return redirect('login/' . $segment);
        })->name('login');

        Route::middleware('guest')->get('/{redirect}',  [LoginController::class,'dynamicLoginUrl'])->name('login.{redirect}');

    });

   

    // Route::any('/{page?}', function () {
    //     return Inertia::render('pages/404'); // Assuming you have a Vue component for 404 at `resources/js/Pages/pages/404.vue`
    // })->where('page', '.*');


    


    Route::get('/status', function() {
        return response()->json(['status' => 'Application is running']);
    });

    Route::get('/healthcheck', function() {
        return response()->json(['status' => 'Healthy']);
    });

    Route::fallback(function () {

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Not Found'], 404);
        }
    
        return Inertia::render('pages/404');
    });



