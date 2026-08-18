<?php

namespace App\Http\Controllers;

use App\Support\VatService;
use Illuminate\Http\Request;

class VatController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->endOfMonth()->toDateString());
        $sheet = VatService::worksheet(auth()->user()->company_id, $from, $to);

        return view('vat.index', compact('sheet', 'from', 'to'));
    }
}
