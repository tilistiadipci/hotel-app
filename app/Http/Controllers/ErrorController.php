<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function error404()
    {
        return view('pages.errors.404');
    }
}
