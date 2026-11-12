<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationsController extends Controller
{
    
    public function chat()
    {
        return view('pages.applications.chat');
    }
    
    public function file_manager()
    {
        return view('pages.applications.file-manager');
    }
    
    public function full_calendar()
    {
        return view('pages.applications.full-calendar');
    }
    
    public function gallery()
    {
        return view('pages.applications.gallery');
    }
    
    public function mail_settings()
    {
        return view('pages.applications.mail-settings');
    }
    
    public function mail()
    {
        return view('pages.applications.mail');
    }
    
    public function sweet_alerts()
    {
        return view('pages.applications.sweet-alerts');
    }
    
    public function task_kanban_board()
    {
        return view('pages.applications.task-kanban-board');
    }
    
    public function task_list_view()
    {
        return view('pages.applications.task-list-view');
    }
    
    public function to_do_list()
    {
        return view('pages.applications.to-do-list');
    }

}
