<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Base\Uuid\UuidModel;
class SingleLandingPage extends Model
{
    use HasFactory,UuidModel,SearchableTrait;

    
    protected $table = 'single_landing_page'; 

   protected $fillable = ['hero_para','hero_img_1','hero_img_2','hero_img_3','hero_img_4','hero_img_5','adv_title','adv_para',
   'adv_box1_title','adv_box1_para','adv_box1_img','adv_box2_title','adv_box2_para','adv_box2_img','adv_box3_title','adv_box3_para',
   'adv_box3_img','adv_box4_title','adv_box4_para','adv_box4_img','adv_box5_title','adv_box5_para','adv_box5_img','app_works_title',
   'app_works_para','app_works_user_title','app_works_driver_title','user_box1_title','user_box1_para','user_box2_title','user_box2_para',
   'user_box3_title','user_box3_para','user_box4_title','user_box4_para','driver_box1_title','driver_box1_para','driver_box2_title',
   'driver_box2_para','driver_box3_title','driver_box3_para','driver_box4_title','driver_box4_para','app_user_img','app_driver_img','why_choose_title',
   'why_choose_box1_title','why_choose_box1_para','why_choose_box2_title','why_choose_box2_para','why_choose_box3_title','why_choose_box3_para',
   'why_choose_box4_title','why_choose_box4_para','why_choose_img','about_title_1','about_title_2','about_img','about_para','ceo_title_1','ceo_title_2',
   'ceo_para','ceo_img','download_title','download_para','download_user_link_android','download_user_link_apple','download_driver_link_android',
   'download_driver_link_apple','download_img1','download_img2','contact_heading','contact_para','contact_address_title','contact_address','contact_phone_title',
   'contact_phone','contact_mail_title','contact_mail','contact_web_title','contact_web','form_name','form_mail','form_subject','form_message','form_btn','contact_img','locale','language','direction'
   ];
}
