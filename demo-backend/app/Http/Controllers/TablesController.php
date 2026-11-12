<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TablesController extends Controller
{
    
    public function data_tables()
    {
        return view('pages.tables.data-tables');
    }

    public function grid_tables()
    {
        return view('pages.tables.grid-tables');
    }
    
    public function tables()
    {
        return view('pages.tables.tables');
    }

}
