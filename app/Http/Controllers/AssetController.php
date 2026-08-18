<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetValuation;
use App\Support\Audit;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::latest()->paginate(15);
        $total = (float) Asset::sum('current_value');

        return view('assets.index', compact('assets', 'total'));
    }

    public function create()
    {
        return view('assets.form', ['asset' => new Asset(['purchase_date' => now(), 'depreciation_method' => 'None', 'condition_status' => 'Good'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['asset_number'] = DocumentNumber::next('AST', 'assets', 'asset_number', auth()->user()->company_id);
        $data['current_value'] = $data['current_value'] ?? $data['purchase_price'];
        $asset = Asset::create($data);
        Audit::log('Create', 'Asset', $asset->id, $asset->asset_number);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset added.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['valuations', 'assignee']);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        return view('assets.form', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $asset->update($this->validated($request));
        Audit::log('Update', 'Asset', $asset->id, $asset->asset_number);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('assets.index')->with('success', 'Asset deleted.');
    }

    public function value(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'valuation_date' => 'required|date',
            'valuation_amount' => 'required|numeric',
            'valuation_reason' => 'nullable|string',
        ]);
        AssetValuation::create([
            'asset_id' => $asset->id,
            'valuation_date' => $data['valuation_date'],
            'valuation_amount' => $data['valuation_amount'],
            'valuation_reason' => $data['valuation_reason'] ?? null,
            'valued_by' => auth()->id(),
        ]);
        $asset->update(['current_value' => $data['valuation_amount']]);

        return back()->with('success', 'Valuation recorded.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'asset_name' => 'required|string|max:100',
            'category' => 'required|string',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric',
            'depreciation_rate' => 'nullable|numeric',
            'depreciation_method' => 'required|string',
            'location' => 'nullable|string',
            'condition_status' => 'required|string',
            'serial_number' => 'nullable|string',
            'warranty_expiry' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
    }
}
