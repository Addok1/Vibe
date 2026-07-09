<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('single_landing_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->longText('hero_para')->nullable();
            $table->text('hero_img_1' ,255)->nullable();
            $table->text('hero_img_2' ,255)->nullable();
            $table->text('hero_img_3' ,255)->nullable();
            $table->text('hero_img_4' ,255)->nullable();
            $table->text('hero_img_5' ,255)->nullable();
            $table->text('adv_title')->nullable();
            $table->longText('adv_para')->nullable();
            $table->text('adv_box1_title')->nullable();
            $table->longText('adv_box1_para')->nullable();
            $table->text('adv_box1_img' ,255)->nullable();
            $table->text('adv_box2_title')->nullable();
            $table->longText('adv_box2_para')->nullable();
            $table->text('adv_box2_img' ,255)->nullable();
            $table->text('adv_box3_title')->nullable();
            $table->longText('adv_box3_para')->nullable();
            $table->text('adv_box3_img' ,255)->nullable();
            $table->text('adv_box4_title')->nullable();
            $table->longText('adv_box4_para')->nullable();
            $table->text('adv_box4_img' ,255)->nullable();
            $table->text('adv_box5_title')->nullable();
            $table->longText('adv_box5_para')->nullable();
            $table->text('adv_box5_img' ,255)->nullable();
            $table->text('app_works_title')->nullable();
            $table->longText('app_works_para')->nullable();
            $table->text('app_works_user_title')->nullable();
            $table->text('app_works_driver_title')->nullable();
            $table->text('user_box1_title')->nullable();
            $table->longText('user_box1_para')->nullable();
            $table->text('user_box2_title')->nullable();
            $table->longText('user_box2_para')->nullable();
            $table->text('user_box3_title')->nullable();
            $table->longText('user_box3_para')->nullable();
            $table->text('user_box4_title')->nullable();
            $table->longText('user_box4_para')->nullable();
            $table->text('driver_box1_title')->nullable();
            $table->longText('driver_box1_para')->nullable();
            $table->text('driver_box2_title')->nullable();
            $table->longText('driver_box2_para')->nullable();
            $table->text('driver_box3_title')->nullable();
            $table->longText('driver_box3_para')->nullable();
            $table->text('driver_box4_title')->nullable();
            $table->longText('driver_box4_para')->nullable();
            $table->text('app_user_img' ,255)->nullable();
            $table->text('app_driver_img' ,255)->nullable();
            $table->text('why_choose_title')->nullable();
            $table->text('why_choose_box1_title')->nullable();
            $table->longText('why_choose_box1_para')->nullable();
            $table->text('why_choose_box2_title')->nullable();
            $table->longText('why_choose_box2_para')->nullable();
            $table->text('why_choose_box3_title')->nullable();
            $table->longText('why_choose_box3_para')->nullable();
            $table->text('why_choose_box4_title')->nullable();
            $table->longText('why_choose_box4_para')->nullable();
            $table->text('why_choose_img' ,255)->nullable();
            $table->text('about_title_1')->nullable();
            $table->text('about_title_2')->nullable();
            $table->text('about_img' ,255)->nullable();
            $table->longText('about_para')->nullable();
            $table->text('ceo_title_1')->nullable();
            $table->text('ceo_title_2')->nullable();
            $table->longText('ceo_para')->nullable();
            $table->text('ceo_img' ,255)->nullable();
            $table->text('download_title')->nullable();
            $table->longText('download_para')->nullable();
            $table->text('download_user_link_android')->nullable();
            $table->text('download_user_link_apple')->nullable();
            $table->text('download_driver_link_android')->nullable();
            $table->text('download_driver_link_apple')->nullable();
            $table->text('download_img1' ,255)->nullable();
            $table->text('download_img2' ,255)->nullable();
            $table->text('contact_heading')->nullable();
            $table->longText('contact_para')->nullable();
            $table->text('contact_img' ,255)->nullable();
            $table->text('contact_address_title')->nullable();
            $table->longText('contact_address')->nullable();
            $table->text('contact_phone_title')->nullable();
            $table->text('contact_phone')->nullable();
            $table->text('contact_mail_title')->nullable();
            $table->text('contact_mail')->nullable();
            $table->text('contact_web_title')->nullable();
            $table->text('contact_web')->nullable();
            $table->text('form_name')->nullable();
            $table->text('form_mail')->nullable();
            $table->text('form_subject')->nullable();
            $table->text('form_message')->nullable();
            $table->text('form_btn')->nullable();
            $table->text('locale')->nullable();
            $table->text('language')->nullable();
             $table->text('direction')->nullable();
             $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('single_landing_page');
    }
};
