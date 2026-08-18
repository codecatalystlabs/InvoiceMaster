<?php

namespace App\Support;

use App\Models\CanteenMeal;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public static function preview(int $companyId, int $year, int $month): array
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $canteenByUser = CanteenMeal::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('meal_date', $year)
            ->whereMonth('meal_date', $month)
            ->whereIn('status', ['approved', 'posted'])
            ->selectRaw('user_id, SUM(total) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $lines = [];
        $totals = [
            'gross' => 0, 'paye' => 0, 'nssf_employee' => 0, 'nssf_employer' => 0,
            'lst' => 0, 'canteen' => 0, 'net' => 0,
        ];

        foreach ($employees as $employee) {
            $line = self::lineFor($employee, (float) ($canteenByUser[$employee->user_id] ?? 0));
            $lines[] = $line;
            foreach ($totals as $key => $_) {
                $totals[$key] += $line[$key];
            }
        }

        return compact('employees', 'lines', 'totals');
    }

    public static function generate(int $companyId, int $year, int $month, ?int $userId = null): PayrollRun
    {
        $exists = PayrollRun::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        if ($exists && $exists->status === 'posted') {
            throw ValidationException::withMessages(['month' => 'That payroll month is already posted.']);
        }

        return DB::transaction(function () use ($companyId, $year, $month, $userId, $exists) {
            $preview = self::preview($companyId, $year, $month);
            $run = $exists ?: new PayrollRun;
            $run->fill([
                'company_id' => $companyId,
                'year' => $year,
                'month' => $month,
                'number' => $run->number ?: DocumentNumber::next('PAYR', 'payroll_runs', 'number', $companyId),
                'status' => 'draft',
                'pay_date' => Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString(),
                'gross' => $preview['totals']['gross'],
                'paye' => $preview['totals']['paye'],
                'nssf_employee' => $preview['totals']['nssf_employee'],
                'nssf_employer' => $preview['totals']['nssf_employer'],
                'lst' => $preview['totals']['lst'],
                'canteen' => $preview['totals']['canteen'],
                'other_deductions' => 0,
                'net' => $preview['totals']['net'],
                'created_by' => $userId,
            ]);
            $run->save();
            $run->items()->delete();

            foreach ($preview['lines'] as $line) {
                PayrollItem::create($line + ['payroll_run_id' => $run->id]);
            }

            return $run->load('items.employee');
        });
    }

    public static function post(PayrollRun $run): PayrollRun
    {
        if ($run->status === 'posted') {
            throw ValidationException::withMessages(['run' => 'Payroll is already posted.']);
        }
        if ($run->items()->count() === 0) {
            throw ValidationException::withMessages(['run' => 'Payroll has no employees.']);
        }

        self::writeLedger($run);
        $run->update(['status' => 'posted', 'posted_at' => now()]);

        return $run;
    }

    public static function writeLedger(PayrollRun $run): void
    {
        $companyId = (int) $run->company_id;
        $salaries = self::account('5130', $companyId);
        $paye = self::account('2120', $companyId);
        $nssfPay = self::account('2130', $companyId);
        $lst = self::account('2140', $companyId);
        $net = self::account('2210', $companyId);
        $canteen = self::account('5160', $companyId);

        $lines = [];
        $salaryDebit = (float) $run->gross + (float) $run->nssf_employer;
        if ($salaries) {
            $lines[] = ['account_id' => $salaries->id, 'entry_type' => 'Debit', 'amount' => $salaryDebit];
        }
        if ($paye && $run->paye > 0) {
            $lines[] = ['account_id' => $paye->id, 'entry_type' => 'Credit', 'amount' => $run->paye];
        }
        if ($nssfPay && ($run->nssf_employee + $run->nssf_employer) > 0) {
            $lines[] = ['account_id' => $nssfPay->id, 'entry_type' => 'Credit', 'amount' => $run->nssf_employee + $run->nssf_employer];
        }
        if ($lst && $run->lst > 0) {
            $lines[] = ['account_id' => $lst->id, 'entry_type' => 'Credit', 'amount' => $run->lst];
        }
        if ($canteen && $run->canteen > 0) {
            $lines[] = ['account_id' => $canteen->id, 'entry_type' => 'Credit', 'amount' => $run->canteen];
        }
        if ($net && $run->net > 0) {
            $lines[] = ['account_id' => $net->id, 'entry_type' => 'Credit', 'amount' => $run->net];
        }

        LedgerService::postJournal(
            'Payroll',
            $run->id,
            $companyId,
            $run->pay_date?->toDateString() ?? now()->toDateString(),
            $run->number,
            'Payroll '.$run->periodLabel(),
            $lines
        );
    }

    protected static function lineFor(Employee $employee, float $canteen): array
    {
        $gross = $employee->gross();
        $nssfEmp = PayrollTax::nssfEmployee($gross);
        $nssfEr = PayrollTax::nssfEmployer($gross);
        $chargeable = max(0, $gross - $nssfEmp);
        $paye = PayrollTax::paye($chargeable);
        $lst = PayrollTax::lst($gross);
        $canteen = min($canteen, max(0, $gross - $nssfEmp - $paye - $lst));
        $net = $gross - $nssfEmp - $paye - $lst - $canteen;

        return [
            'employee_id' => $employee->id,
            'basic' => $employee->basic_salary,
            'allowances' => $employee->allowances,
            'gross' => $gross,
            'paye' => $paye,
            'nssf_employee' => $nssfEmp,
            'nssf_employer' => $nssfEr,
            'lst' => $lst,
            'canteen' => $canteen,
            'other_deductions' => 0,
            'net' => $net,
        ];
    }

    protected static function account(string $code, int $companyId)
    {
        return \App\Models\ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('account_code', $code)
            ->first();
    }
}
