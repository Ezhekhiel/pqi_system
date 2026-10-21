<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class pqiController extends Controller
{
    public function index() {
        return view('pqi.index');
    }
    public function export() {
        return view('pqi.export');
    }
}
