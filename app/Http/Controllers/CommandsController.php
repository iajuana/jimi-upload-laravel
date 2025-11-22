<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommandsController extends Controller
{
    public function index(Request $request)
    {
        $phone = (string)$request->query('phone', '');
        $command = (string)$request->query('command', '');
        return view('comandos', compact('phone','command'));
    }
}


