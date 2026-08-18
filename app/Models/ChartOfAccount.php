<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'account_code', 'account_name', 'account_type',
        'parent_id', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function seedDefaults(int $companyId): void
    {
        $accounts = [
            ['1000', 'Assets', 'Asset', 'Main asset account'],
            ['1100', 'Current Assets', 'Asset', 'Short-term assets'],
            ['1110', 'Cash', 'Asset', 'Cash on hand'],
            ['1115', 'Petty Cash', 'Asset', 'Petty cash float on hand'],
            ['1120', 'Bank Account', 'Asset', 'Money in bank accounts'],
            ['1130', 'Accounts Receivable', 'Asset', 'Money owed by customers'],
            ['1140', 'VAT Receivable', 'Asset', 'Input VAT on purchases'],
            ['1200', 'Fixed Assets', 'Asset', 'Long-term assets'],
            ['1210', 'Equipment', 'Asset', 'Office and business equipment'],
            ['1220', 'Furniture', 'Asset', 'Office furniture'],
            ['1230', 'Vehicles', 'Asset', 'Company vehicles'],
            ['2000', 'Liabilities', 'Liability', 'Main liability account'],
            ['2110', 'Accounts Payable', 'Liability', 'Money owed to suppliers'],
            ['2120', 'PAYE Payable', 'Liability', 'PAYE withheld from payroll'],
            ['2130', 'NSSF Payable', 'Liability', 'NSSF contributions payable'],
            ['2140', 'LST Payable', 'Liability', 'Local Service Tax payable'],
            ['2150', 'VAT Payable', 'Liability', 'Output VAT on sales'],
            ['2210', 'Net Salaries Payable', 'Liability', 'Net pay awaiting disbursement'],
            ['3000', 'Equity', 'Equity', 'Owner equity'],
            ['3100', 'Owner Capital', 'Equity', 'Capital invested by owners'],
            ['3200', 'Retained Earnings', 'Equity', 'Accumulated profits'],
            ['4000', 'Revenue', 'Revenue', 'Main revenue account'],
            ['4100', 'Sales Revenue', 'Revenue', 'Income from sales'],
            ['4200', 'Service Revenue', 'Revenue', 'Income from services'],
            ['5000', 'Expenses', 'Expense', 'Main expense account'],
            ['5110', 'Rent', 'Expense', 'Office and property rent'],
            ['5120', 'Utilities', 'Expense', 'Electricity, water, internet'],
            ['5130', 'Salaries', 'Expense', 'Employee salaries and wages'],
            ['5140', 'Office Supplies', 'Expense', 'Stationery and supplies'],
            ['5150', 'Marketing', 'Expense', 'Advertising and promotion'],
            ['5160', 'Canteen / Staff meals', 'Expense', 'Approved staff canteen meals'],
            ['5170', 'Petty cash spend', 'Expense', 'Accounted petty cash expenditure'],
            ['5200', 'Software & Subscriptions', 'Expense', 'Software licenses and subscriptions'],
        ];

        foreach ($accounts as [$code, $name, $type, $desc]) {
            static::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'account_code' => $code],
                ['account_name' => $name, 'account_type' => $type, 'description' => $desc, 'is_active' => true]
            );
        }
    }
}
