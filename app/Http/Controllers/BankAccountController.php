<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\StatementImport;
use App\Support\BankStatementService;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::with('ledgerAccount')->orderByDesc('is_default')->orderBy('name')->get();

        return view('banks.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:40',
            'ledger_account_id' => 'nullable|exists:chart_of_accounts,id',
            'is_default' => 'nullable|boolean',
        ]);
        if (! empty($data['is_default'])) {
            BankAccount::query()->update(['is_default' => false]);
        }
        BankAccount::create($data + ['currency' => auth()->user()->company->currency ?: 'UGX', 'is_default' => $request->boolean('is_default')]);

        return back()->with('success', 'Bank account saved.');
    }

    public function statements(BankAccount $bank)
    {
        $imports = StatementImport::with('lines')->where('bank_account_id', $bank->id)->latest()->get();
        $ledgers = ChartOfAccount::where('account_type', 'Asset')->orderBy('account_code')->get();

        return view('banks.statements', compact('bank', 'imports', 'ledgers'));
    }

    public function import(Request $request, BankAccount $bank)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', function (string $attribute, $file, $fail) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, ['pdf', 'csv', 'txt'], true)) {
                    $fail('Upload a PDF or CSV bank statement.');
                }
            }],
        ]);
        BankStatementService::import($bank, $request->file('file'), auth()->id());

        return back()->with('success', 'Statement imported. Review suggested matches.');
    }
}
