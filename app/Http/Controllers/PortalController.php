<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;

class PortalController extends Controller
{
    public function show(string $token)
    {
        $client = Client::withoutGlobalScopes()->where('portal_token', $token)->firstOrFail();
        $invoices = Invoice::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->latest()
            ->get();
        $company = $client->company ?? $invoices->first()?->company;

        return view('portal.show', compact('client', 'invoices', 'company'));
    }
}
