<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Redirect based on user role
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        return view('home');
    }
}
