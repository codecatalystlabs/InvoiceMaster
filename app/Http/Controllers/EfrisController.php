<?php

namespace App\Http\Controllers;

use App\Models\EfrisSubmission;
use App\Models\Invoice;
use App\Support\EfrisService;

class EfrisController extends Controller
{
    public function index()
    {
        $rows = EfrisSubmission::with('invoice')->latest()->paginate(20);

        return view('efris.index', compact('rows'));
    }

    public function queue(Invoice $invoice)
    {
        EfrisService::queue($invoice);

        return back()->with('success', 'Invoice queued for URA EFRIS.');
    }
}
