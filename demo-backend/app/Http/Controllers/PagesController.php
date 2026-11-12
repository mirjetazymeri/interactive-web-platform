<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PagesController extends Controller
{
    
    public function blog_create()
    {
        return view('pages.pages.blog-create');
    }
    
    public function blog_details()
    {
        return view('pages.pages.blog-details');
    }
    
    public function blog()
    {
        return view('pages.pages.blog');
    }
    
    public function coming_soon()
    {
        return view('pages.pages.coming-soon');
    }
    
    public function create_password_basic()
    {
        return view('pages.pages.create-password-basic');
    }
    
    public function create_password_cover()
    {
        return view('pages.pages.create-password-cover');
    }
    
    public function emptypage()
    {
        return view('pages.pages.emptypage');
    }
    
    public function error401()
    {
        return view('pages.pages.error401');
    }
    
    public function error404()
    {
        return view('pages.pages.error404');
    }
    
    public function error500()
    {
        return view('pages.pages.error500');
    }
    
    public function faqs()
    {
        return view('pages.pages.faqs');
    }
    
    public function floating_labels()
    {
        return view('pages.pages.floating-labels');
    }
    
    public function form_advanced()
    {
        return view('pages.pages.form-advanced');
    }
    
    public function form_check_radios()
    {
        return view('pages.pages.form-check-radios');
    }
    
    public function form_color_pickers()
    {
        return view('pages.pages.form-color-pickers');
    }
    
    public function form_datetime_pickers()
    {
        return view('pages.pages.form-datetime-pickers');
    }
    
    public function form_file_uploads()
    {
        return view('pages.pages.form-file-uploads');
    }
    
    public function form_input_group()
    {
        return view('pages.pages.form-input-group');
    }
    
    public function form_input_masks()
    {
        return view('pages.pages.form-input-masks');
    }
    
    public function form_inputs()
    {
        return view('pages.pages.form-inputs');
    }
    
    public function form_layout()
    {
        return view('pages.pages.form-layout');
    }
    
    public function form_range()
    {
        return view('pages.pages.form-range');
    }
    
    public function form_select()
    {
        return view('pages.pages.form-select');
    }
    
    public function form_select2()
    {
        return view('pages.pages.form-select2');
    }
    
    public function form_validation()
    {
        return view('pages.pages.form-validation');
    }
    
    public function form_wizards()
    {
        return view('pages.pages.form-wizards');
    }
    
    public function invoice_create()
    {
        return view('pages.pages.invoice-create');
    }
    
    public function invoice_details()
    {
        return view('pages.pages.invoice-details');
    }
    
    public function invoice_list()
    {
        return view('pages.pages.invoice-list');
    }
    
    public function landing()
    {
        return view('pages.pages.landing');
    }
    
    public function lockscreen_basic()
    {
        return view('pages.pages.lockscreen-basic');
    }
    
    public function lockscreen_cover()
    {
        return view('pages.pages.lockscreen-cover');
    }
    
    public function pricing()
    {
        return view('pages.pages.pricing');
    }
    
    public function profile_settings()
    {
        return view('pages.pages.profile-settings');
    }
    
    public function profile()
    {
        return view('pages.pages.profile');
    }
    
    public function quill_editor()
    {
        return view('pages.pages.quill-editor');
    }
    
    public function reset_password_basic()
    {
        return view('pages.pages.reset-password-basic');
    }
    
    public function reset_password_cover()
    {
        return view('pages.pages.reset-password-cover');
    }
    
    public function search_results()
    {
        return view('pages.pages.search-results');
    }
    
    public function sign_in_basic()
    {
        return view('pages.pages.sign-in-basic');
    }
    
    public function sign_in_cover()
    {
        return view('pages.pages.sign-in-cover');
    }
    
    public function sign_up_basic()
    {
        return view('pages.pages.sign-up-basic');
    }
    
    public function sign_up_cover()
    {
        return view('pages.pages.sign-up-cover');
    }
    
    public function team()
    {
        return view('pages.pages.team');
    }
    
    public function terms_conditions()
    {
        return view('pages.pages.terms-conditions');
    }
    
    public function testimonials()
    {
        return view('pages.pages.testimonials');
    }
    
    public function timeline()
    {
        return view('pages.pages.timeline');
    }
    
    public function two_step_verification_basic()
    {
        return view('pages.pages.two-step-verification-basic');
    }
    
    public function two_step_verification_cover()
    {
        return view('pages.pages.two-step-verification-cover');
    }
    
    public function under_maintenance()
    {
        return view('pages.pages.under-maintenance');
    }

}
