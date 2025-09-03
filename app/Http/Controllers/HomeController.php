<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class HomeController extends Controller
{
    public function index()
    {
        return view('index');
    }
}
