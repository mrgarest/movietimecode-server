<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class PrivacyController extends Controller
{
    public function index()
    {
        return view('privacy', [
            'SEOData' => new SEOData(
                robots: 'noindex, nofollow',
                title: 'Політика конфіденційності'
            ),
        ]);
    }
}
