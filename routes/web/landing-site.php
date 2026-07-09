<?php

use App\Http\Controllers\LandingHeaderController;
use App\Http\Controllers\LandingHomeController;
use App\Http\Controllers\LandingDriverController;
use App\Http\Controllers\LandingUserController;
use App\Http\Controllers\LandingContactController;
use App\Http\Controllers\LandingAboutsController;
use App\Http\Controllers\LandingQuickLinkController;
use App\Http\Controllers\LandingSiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SingleLandingPageController;
use App\Http\Controllers\SinglePageLandingHeaderController;

// landing template
// Route::group(['prefix' => 'landing'], function () {
Route::get('/', [LandingHomeController::class, 'homepage'])->name('landing.index');
Route::get('/driver', [LandingDriverController::class, 'driverpage'])->name('landing.driver');
Route::get('/aboutus', [LandingAboutsController::class, 'aboutuspage'])->name('landing.aboutus');
Route::get('/user', [LandingUserController::class, 'userpage'])->name('landing.user');
Route::get('/contact', [LandingContactController::class, 'contactpage'])->name('landing.contact');
Route::get('/privacy', [LandingQuickLinkController::class, 'privacypage'])->name('landing.privacy');
Route::get('/compliance', [LandingQuickLinkController::class, 'compliancepage'])->name('landing.compliance');
Route::get('/terms', [LandingQuickLinkController::class, 'termspage'])->name('landing.terms');
Route::get('/dmv', [LandingQuickLinkController::class, 'dmvpage'])->name('landing.dmv');




// landingsite-homepage 
Route::group(['prefix' => 'landing-home', 'middleware' => 'permission:landing_home'], function () {
    Route::get('/', [LandingHomeController::class, 'index'])->name('landing_home.index');
    Route::middleware('remove_empty_query')->get('/list', [LandingHomeController::class, 'list'])->name('landing_home.list');
    Route::get('/create', [LandingHomeController::class, 'create'])->name('landing_home.create');
    Route::post('/store', [LandingHomeController::class, 'store'])->name('landing_home.store');
    Route::get('/edit/{id}', [LandingHomeController::class, 'edit'])->name('landing_home.edit');
    Route::post('/update/{landingHome}', [LandingHomeController::class, 'update'])->name('landing_home.update');
    Route::delete('/delete/{landingHome}', [LandingHomeController::class, 'destroy'])->name('landing_home.delete');
});


// landingsite-aboutus 
Route::group(['prefix' => 'landing-aboutus', 'middleware' => 'permission:landing_aboutus'], function () {
    Route::get('/', [LandingAboutsController::class, 'index'])->name('landing_abouts.index');
    Route::middleware('remove_empty_query')->get('/list', [LandingAboutsController::class, 'list'])->name('landing_abouts.list');
    Route::get('/create', [LandingAboutsController::class, 'create'])->name('landing_abouts.create');
    Route::post('/store', [LandingAboutsController::class, 'store'])->name('landing_abouts.store');
    Route::get('/edit/{id}', [LandingAboutsController::class, 'edit'])->name('landing_abouts.edit');
    Route::post('/update/{landingAbouts}', [LandingAboutsController::class, 'update'])->name('landing_abouts.update');
    Route::delete('/delete/{landingAbouts}', [LandingAboutsController::class, 'destroy'])->name('landing_abouts.delete');
});

Route::group(['prefix' => 'landing-driver', 'middleware' => 'permission:landing_driver'], function () {
    Route::get('/', [LandingDriverController::class, 'index'])->name('landing_driver.index');
    Route::middleware('remove_empty_query')->get('/list', [LandingDriverController::class, 'list'])->name('landing_driver.list');
    Route::get('/create', [LandingDriverController::class, 'create'])->name('landing_driver.create');
    Route::post('/store', [LandingDriverController::class, 'store'])->name('landing_driver.store');
    Route::get('/edit/{id}', [LandingDriverController::class, 'edit'])->name('landing_driver.edit');
    Route::post('/update/{landingDriver}', [LandingDriverController::class, 'update'])->name('landing_driver.update');
    Route::delete('/delete/{landingDriver}', [LandingDriverController::class, 'destroy'])->name('landing_driver.delete');
});

Route::group(['prefix' => 'landing-user', 'middleware' => 'permission:landing_user'], function () {
    Route::get('/', [LandingUserController::class, 'index'])->name('landing_user.index');
    Route::middleware('remove_empty_query')->get('/list', [LandingUserController::class, 'list'])->name('landing_user.list');
    Route::get('/create', [LandingUserController::class, 'create'])->name('landing_user.create');
    Route::post('/store', [LandingUserController::class, 'store'])->name('landing_user.store');
    Route::get('/edit/{id}', [LandingUserController::class, 'edit'])->name('landing_user.edit');
    Route::post('/update/{landingUser}', [LandingUserController::class, 'update'])->name('landing_user.update');
    Route::delete('/delete/{landingUser}', [LandingUserController::class, 'destroy'])->name('landing_user.delete');
});

// landingsite-Header-Footer 
Route::group(['prefix' => 'landing-header', 'middleware' => 'permission:header_footer'], function () {
    Route::get('/', [LandingHeaderController::class, 'home'])->name('landing_header.index');
    Route::get('/list', [LandingHeaderController::class, 'list'])->name('landing_header.list');
    Route::get('/create', [LandingHeaderController::class, 'create'])->name('landing_header.create');
    Route::post('/store', [LandingHeaderController::class, 'store'])->name('landing_header.store');
    Route::get('/edit/{id}', [LandingHeaderController::class, 'edit'])->name('landing_header.edit');
    Route::post('/update/{landingHeader}', [LandingHeaderController::class, 'update'])->name('landing_header.update');
    Route::delete('/delete/{landingHeader}', [LandingHeaderController::class, 'destroy'])->name('landing_header.delete');
    Route::get('/get-color-settings', [LandingHeaderController::class, 'getColorSettings']);
    Route::post('/update-color-settings', [LandingHeaderController::class, 'updateColorSettings']);
});

// landingsite-Quick Links 
Route::group(['prefix' => 'landing-quicklink', 'middleware' => 'permission:landing_quicklinks'], function () {
    Route::get('/', [LandingQuickLinkController::class, 'index'])->name('landing_quicklink.index');
    Route::get('/list', [LandingQuickLinkController::class, 'list'])->name('landing_quicklink.list');
    Route::get('/create', [LandingQuickLinkController::class, 'create'])->name('landing_quicklink.create');
    Route::post('/store', [LandingQuickLinkController::class, 'store'])->name('landing_quicklink.store');
    Route::get('/edit/{id}', [LandingQuickLinkController::class, 'edit'])->name('landing_quicklink.edit');
    Route::post('/update/{landingQuickLink}', [LandingQuickLinkController::class, 'update'])->name('landing_quicklink.update');
    Route::delete('/delete/{landingQuickLink}', [LandingQuickLinkController::class, 'destroy'])->name('landing_quicklink.delete');
});

// landingsite-Contact 
Route::group(['prefix' => 'landing-contact', 'middleware' => 'permission:landing_contact'], function () {
    Route::get('/', [LandingContactController::class, 'index'])->name('landing_contact.index');
    Route::get('/list', [LandingContactController::class, 'list'])->name('landing_contact.list');
    Route::get('/create', [LandingContactController::class, 'create'])->name('landing_contact.create');
    Route::post('/store', [LandingContactController::class, 'store'])->name('landing_contact.store');
    Route::get('/edit/{id}', [LandingContactController::class, 'edit'])->name('landing_contact.edit');
    Route::post('/update/{landingContact}', [LandingContactController::class, 'update'])->name('landing_contact.update');
    Route::delete('/delete/{landingContact}', [LandingContactController::class, 'destroy'])->name('landing_contact.delete');
});
// landingsite-Contact 
Route::group(['prefix' => 'landing-contact'], function () {
    Route::post('/contactmessage', [LandingContactController::class, 'contact_message'])->name('landing_contact.contactmessage');
});
// single landing page
Route::group(['prefix' => 'single-landing-page', 'middleware' => 'permission:single_landing_page'], function () {
    Route::get('/', [SingleLandingPageController::class, 'index'])->name('singlelandingpage.index');
    Route::get('/list', [SingleLandingPageController::class, 'list'])->name('singlelandingpage.list');
    Route::get('/create', [SingleLandingPageController::class, 'create'])->name('singlelandingpage.create');
    Route::post('/store', [SingleLandingPageController::class, 'store'])->name('singlelandingpage.store');
    Route::get('/edit/{id}', [SingleLandingPageController::class, 'edit'])->name('singlelandingpage.edit');
    Route::post('/update/{singlelandingpage}', [SingleLandingPageController::class, 'update'])->name('singlelandingpage.update');
    Route::delete('/delete/{singlelandingpage}', [SingleLandingPageController::class, 'destroy'])->name('singlelandingpage.delete');
});
// single landing page
Route::group(['prefix' => 'single-landing-page'], function () {
    Route::post('/contactmessage', [SingleLandingPageController::class, 'contact_message'])->name('singlelandingpage.contactmessage');
});

// single-landingsite-Header-Footer 
Route::group(['prefix' => 'single-landing-header-footer', 'middleware' => 'permission:single_header_footer'], function () {
    Route::get('/', [SinglePageLandingHeaderController::class, 'home'])->name('single_landing_header.index');
    Route::get('/list', [SinglePageLandingHeaderController::class, 'list'])->name('single_landing_header.list');
    Route::get('/create', [SinglePageLandingHeaderController::class, 'create'])->name('single_landing_header.create');
    Route::post('/store', [SinglePageLandingHeaderController::class, 'store'])->name('single_landing_header.store');
    Route::get('/edit/{id}', [SinglePageLandingHeaderController::class, 'edit'])->name('single_landing_header.edit');
    Route::post('/update/{singlelandingHeader}', [SinglePageLandingHeaderController::class, 'update'])->name('single_landing_header.update');
    Route::delete('/delete/{singlelandingHeader}', [SinglePageLandingHeaderController::class, 'destroy'])->name('single_landing_header.delete');
    Route::get('/get-color-settings', [SinglePageLandingHeaderController::class, 'getColorSettings']);
    Route::post('/update-color-settings', [SinglePageLandingHeaderController::class, 'updateColorSettings']);
});

