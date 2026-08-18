<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\Audit;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $clients = Client::when($q, fn ($query) => $query->where(function ($w) use ($q) {
            $w->where('name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")
                ->orWhere('company', 'like', "%$q%");
        }))->latest()->paginate(15)->withQueryString();

        return view('clients.index', compact('clients', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);
        $client = Client::create($data);
        Audit::log('Create', 'Client', $client->id, $client->name);

        return back()->with('success', 'Client added.');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);
        $client->update($data);
        Audit::log('Update', 'Client', $client->id, $client->name);

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        Audit::log('Delete', 'Client', $client->id, $client->name);
        $client->delete();

        return back()->with('success', 'Client deleted.');
    }
}
