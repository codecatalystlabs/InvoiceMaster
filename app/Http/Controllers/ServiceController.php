<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServicePayment;
use App\Support\Audit;
use App\Support\CashBookService;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(15);
        $monthly = Service::where('status', 'Active')->get()->sum(function ($s) {
            return match ($s->billing_frequency) {
                'Monthly' => $s->cost,
                'Quarterly' => $s->cost / 3,
                'Yearly' => $s->cost / 12,
                'Weekly' => $s->cost * 4,
                default => $s->cost,
            };
        });

        return view('services.index', compact('services', 'monthly'));
    }

    public function create()
    {
        return view('services.form', ['service' => new Service(['start_date' => now(), 'next_billing_date' => now()->addMonth(), 'billing_frequency' => 'Monthly', 'status' => 'Active'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['service_number'] = DocumentNumber::next('SRV', 'services', 'service_number', auth()->user()->company_id);
        $service = Service::create($data);
        Audit::log('Create', 'Service', $service->id, $service->service_number);

        return redirect()->route('services.show', $service)->with('success', 'Service added.');
    }

    public function show(Service $service)
    {
        $service->load('payments');

        return view('services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        return view('services.form', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validated($request));

        return redirect()->route('services.show', $service)->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted.');
    }

    public function pay(Request $request, Service $service)
    {
        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        $payment = ServicePayment::create($data + ['service_id' => $service->id, 'created_by' => auth()->id()]);
        CashBookService::record([
            'entry_date' => $payment->payment_date,
            'description' => 'Service payment '.$service->service_name,
            'type' => 'credit',
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'service_id' => $service->id,
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'service_name' => 'required|string',
            'provider_name' => 'required|string',
            'provider_contact' => 'nullable|string',
            'category' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'billing_frequency' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'next_billing_date' => 'required|date',
            'status' => 'required|string',
            'description' => 'nullable|string',
        ]);
        $data['auto_renew'] = $request->boolean('auto_renew');

        return $data;
    }
}
