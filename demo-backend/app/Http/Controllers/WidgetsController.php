<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WidgetsController extends Controller
{
    
    public function widgets()
    {
        return view('pages.widgets.widgets');
    }

}
