<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MapsController extends Controller
{
    
    public function google_maps()
    {
        return view('pages.maps.google-maps');
    }

    public function leaflet_maps()
    {
        return view('pages.maps.leaflet-maps');
    }

    public function vector_maps()
    {
        return view('pages.maps.vector-maps');
    }

}
