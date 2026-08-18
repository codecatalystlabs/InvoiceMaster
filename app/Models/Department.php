<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code', 'head_user_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(AnnualBudget::class);
    }

    public function pettyCashFunds(): HasMany
    {
        return $this->hasMany(PettyCashFund::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class)->orderBy('sort_order')->orderBy('name');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Software-company org chart: departments, divisions, and positions.
     */
    public static function seedDefaults(int $companyId): void
    {
        $tree = [
            ['EXE', 'Leadership', 10, [
                ['CEO', 'Office of the CEO', 10],
            ], [
                ['CEO', 'Chief Executive Officer', 'executive', 'CEO', 10],
                ['COO', 'Chief Operating Officer', 'executive', 'CEO', 20],
            ]],
            ['ENG', 'Engineering', 20, [
                ['SWE', 'Software Engineering', 10],
                ['QA', 'Quality Assurance', 20],
                ['CLOUD', 'DevOps & Cloud', 30],
            ], [
                ['CTO', 'Chief Technology Officer', 'executive', 'SWE', 10],
                ['EM', 'Engineering Manager', 'manager', 'SWE', 20],
                ['TL', 'Tech Lead', 'lead', 'SWE', 30],
                ['SSE', 'Senior Software Engineer', 'senior', 'SWE', 40],
                ['SWE', 'Software Engineer', 'mid', 'SWE', 50],
                ['JSE', 'Junior Software Engineer', 'junior', 'SWE', 60],
                ['INT', 'Software Engineering Intern', 'intern', 'SWE', 70],
                ['MOB', 'Mobile Engineer', 'mid', 'SWE', 80],
                ['SQA', 'Senior QA Engineer', 'senior', 'QA', 90],
                ['QAE', 'QA Engineer', 'mid', 'QA', 100],
                ['SDE', 'Senior DevOps Engineer', 'senior', 'CLOUD', 110],
                ['DEV', 'DevOps Engineer', 'mid', 'CLOUD', 120],
            ]],
            ['PRD', 'Product & Design', 30, [
                ['PRO', 'Product', 10],
                ['DES', 'Design', 20],
            ], [
                ['HPD', 'Head of Product', 'director', 'PRO', 10],
                ['PM', 'Product Manager', 'manager', 'PRO', 20],
                ['PO', 'Product Owner', 'mid', 'PRO', 30],
                ['SXD', 'Senior Product Designer', 'senior', 'DES', 40],
                ['UXD', 'UI/UX Designer', 'mid', 'DES', 50],
            ]],
            ['DEL', 'Delivery', 40, [
                ['PMO', 'Project Management', 10],
                ['CSS', 'Client Success', 20],
            ], [
                ['PGM', 'Project Manager', 'manager', 'PMO', 10],
                ['BA', 'Business Analyst', 'mid', 'PMO', 20],
                ['CSM', 'Client Success Manager', 'mid', 'CSS', 30],
                ['SUP', 'Support Officer', 'junior', 'CSS', 40],
            ]],
            ['COM', 'Commercial', 50, [
                ['SAL', 'Sales', 10],
                ['MKT', 'Marketing', 20],
            ], [
                ['HSC', 'Head of Sales', 'director', 'SAL', 10],
                ['SL', 'Sales Lead', 'lead', 'SAL', 20],
                ['AE', 'Account Executive', 'mid', 'SAL', 30],
                ['MO', 'Marketing Officer', 'mid', 'MKT', 40],
            ]],
            ['FIN', 'Finance & Accounts', 60, [
                ['ACC', 'Accounting', 10],
            ], [
                ['FM', 'Finance Manager', 'manager', 'ACC', 10],
                ['ACC', 'Accountant', 'mid', 'ACC', 20],
                ['FO', 'Finance Officer', 'junior', 'ACC', 30],
            ]],
            ['ADM', 'People & Administration', 70, [
                ['HR', 'Human Resources', 10],
                ['OFF', 'Office Administration', 20],
            ], [
                ['HRO', 'HR Officer', 'mid', 'HR', 10],
                ['OA', 'Office Administrator', 'junior', 'OFF', 20],
            ]],
            ['OPS', 'Operations', 80, [
                ['FAC', 'Facilities', 10],
            ], [
                ['PROC', 'Procurement Officer', 'mid', 'FAC', 10],
            ]],
        ];

        $rename = [
            'OPS' => ['Operations'],
            'FIN' => ['Finance', 'Finance & Accounts'],
            'ENG' => ['Engineering'],
            'ADM' => ['Administration', 'People & Administration'],
        ];

        foreach ($tree as $row) {
            [$code, $name, $order, $divisions, $positions] = $row;
            $dept = static::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                ['name' => $name, 'is_active' => true, 'sort_order' => $order]
            );
            $allowed = $rename[$code] ?? [$name];
            if (in_array($dept->name, $allowed, true) || $dept->wasRecentlyCreated) {
                $dept->fill(['name' => $name, 'sort_order' => $order, 'is_active' => true])->save();
            }

            $divisionIds = [];
            foreach ($divisions as $div) {
                [$dCode, $dName, $dOrder] = $div;
                $division = Division::withoutGlobalScopes()->firstOrCreate(
                    ['department_id' => $dept->id, 'code' => $dCode],
                    [
                        'company_id' => $companyId,
                        'name' => $dName,
                        'sort_order' => $dOrder,
                        'is_active' => true,
                    ]
                );
                if ($division->wasRecentlyCreated || $division->name !== $dName) {
                    $division->fill(['name' => $dName, 'sort_order' => $dOrder, 'company_id' => $companyId])->save();
                }
                $divisionIds[$dCode] = $division->id;
            }

            foreach ($positions as $pos) {
                [$pCode, $pName, $level, $divCode, $pOrder] = $pos;
                Position::withoutGlobalScopes()->updateOrCreate(
                    ['company_id' => $companyId, 'code' => $pCode],
                    [
                        'department_id' => $dept->id,
                        'division_id' => $divisionIds[$divCode] ?? null,
                        'name' => $pName,
                        'level' => $level,
                        'sort_order' => $pOrder,
                        'is_active' => true,
                    ]
                );
            }
        }

        Holiday::seedDefaults($companyId);
    }
}
