<?php

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('accent_color');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('address');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('pay_token', 64)->nullable()->unique()->after('invoice_number');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('total');
            $table->unsignedBigInteger('project_id')->nullable()->after('quotation_id');
            $table->unsignedBigInteger('service_id')->nullable()->after('project_id');
            $table->boolean('is_recurring')->default(false)->after('notes');
            $table->string('recurrence_frequency', 30)->nullable()->after('is_recurring');
            $table->date('next_recurrence_date')->nullable()->after('recurrence_frequency');
            $table->unsignedBigInteger('recurrence_parent_id')->nullable()->after('next_recurrence_date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('account_id');
            $table->decimal('tax', 15, 2)->default(0)->after('amount');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('client_id');
        });

        Schema::table('cash_book_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('account_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->decimal('amount', 15, 2);
            $table->string('method', 40);
            $table->string('phone')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('paid');
            $table->string('provider', 40)->default('manual');
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 10)->default('UGX');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('filename')->nullable();
            $table->unsignedInteger('line_count')->default(0);
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_import_id')->constrained()->cascadeOnDelete();
            $table->date('line_date')->nullable();
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('match_type', 30)->nullable();
            $table->unsignedBigInteger('match_id')->nullable();
            $table->string('status', 20)->default('unmatched');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tin')->nullable();
            $table->string('nssf_number')->nullable();
            $table->string('job_title')->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->string('pay_method', 40)->default('bank');
            $table->string('pay_account')->nullable();
            $table->string('status', 20)->default('Active');
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('number');
            $table->string('status', 20)->default('draft');
            $table->date('pay_date')->nullable();
            $table->decimal('gross', 15, 2)->default(0);
            $table->decimal('paye', 15, 2)->default(0);
            $table->decimal('nssf_employee', 15, 2)->default(0);
            $table->decimal('nssf_employer', 15, 2)->default(0);
            $table->decimal('lst', 15, 2)->default(0);
            $table->decimal('canteen', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'year', 'month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('gross', 15, 2)->default(0);
            $table->decimal('paye', 15, 2)->default(0);
            $table->decimal('nssf_employee', 15, 2)->default(0);
            $table->decimal('nssf_employer', 15, 2)->default(0);
            $table->decimal('lst', 15, 2)->default(0);
            $table->decimal('canteen', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status', 20)->default('Active');
            $table->decimal('budget', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('number');
            $table->string('vendor_name');
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('status', 20)->default('Open');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
        });

        Schema::create('efris_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('queued');
            $table->string('fdn')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notice_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('to');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('invoices')->whereNull('pay_token')->pluck('id') as $id) {
            DB::table('invoices')->where('id', $id)->update(['pay_token' => Str::random(48)]);
        }
        foreach (DB::table('invoices')->get(['id', 'company_id']) as $inv) {
            $paid = (float) DB::table('receipts')->where('invoice_id', $inv->id)->sum('amount');
            $status = DB::table('invoices')->where('id', $inv->id)->value('status');
            if ($paid > 0 && ! in_array($status, ['Paid', 'paid', 'Cancelled', 'cancelled'], true)) {
                $total = (float) DB::table('invoices')->where('id', $inv->id)->value('total');
                $status = $paid + 0.009 >= $total ? 'Paid' : 'Partially Paid';
            }
            DB::table('invoices')->where('id', $inv->id)->update(['amount_paid' => $paid, 'status' => $status]);
        }
        foreach (DB::table('clients')->whereNull('portal_token')->pluck('id') as $id) {
            DB::table('clients')->where('id', $id)->update(['portal_token' => Str::random(48)]);
        }

        foreach (Company::withoutGlobalScopes()->get() as $company) {
            ChartOfAccount::seedDefaults($company->id);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_logs');
        Schema::dropIfExists('efris_submissions');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('statement_lines');
        Schema::dropIfExists('statement_imports');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('payments');
        Schema::table('cash_book_entries', function (Blueprint $table) {
            $table->dropColumn('bank_account_id');
        });
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['project_id', 'tax']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'pay_token', 'amount_paid', 'project_id', 'service_id',
                'is_recurring', 'recurrence_frequency', 'next_recurrence_date', 'recurrence_parent_id',
            ]);
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('portal_token');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
