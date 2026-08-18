<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Support\DocumentExport;
use App\Support\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index()
    {
        if (auth()->user()->seesOnlyOwnRecords()) {
            $items = PayrollItem::query()
                ->whereHas('employee', fn ($q) => $q->where('user_id', auth()->id()))
                ->with(['run', 'employee'])
                ->latest()
                ->paginate(20);

            return view('payroll.mine', compact('items'));
        }

        $runs = PayrollRun::withCount('items')->orderByDesc('year')->orderByDesc('month')->paginate(20);

        return view('payroll.index', compact('runs'));
    }

    public function create(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $preview = PayrollService::preview(auth()->user()->company_id, $year, $month);

        return view('payroll.create', compact('year', 'month', 'preview'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        $run = PayrollService::generate(auth()->user()->company_id, (int) $data['year'], (int) $data['month'], auth()->id());

        return redirect()->route('payroll.show', $run)->with('success', 'Payroll draft prepared.');
    }

    public function show(PayrollRun $payroll)
    {
        $payroll->load('items.employee');

        return view('payroll.show', ['run' => $payroll]);
    }

    public function post(PayrollRun $payroll)
    {
        PayrollService::post($payroll);

        return back()->with('success', 'Payroll posted to the ledger.');
    }

    public function payslip(Request $request, PayrollItem $item)
    {
        $item->load(['employee', 'run.company']);
        abort_unless($item->run && $item->run->company_id === auth()->user()->company_id, 404);
        if (auth()->user()->seesOnlyOwnRecords()) {
            abort_unless($item->employee?->user_id === auth()->id(), 403);
        }
        $company = $item->run->company ?? auth()->user()->company;
        $pdf = DocumentExport::payslipPdf($item, $company);

        return DocumentExport::sendPdf($pdf, 'payslip-'.$item->run->periodLabel().'-'.$item->employee->number.'.pdf', $request->boolean('inline'));
    }

    public function bulkPay(PayrollRun $payroll)
    {
        $payroll->load('items.employee');
        $lines = ["Name,Method,Account,Amount,Reference"];
        foreach ($payroll->items as $item) {
            $lines[] = implode(',', [
                '"'.str_replace('"', '', $item->employee->name).'"',
                $item->employee->pay_method,
                $item->employee->pay_account,
                number_format((float) $item->net, 0, '.', ''),
                $payroll->number,
            ]);
        }
        $csv = implode("\n", $lines);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$payroll->number.'-momo-pay.csv"',
        ]);
    }
}
