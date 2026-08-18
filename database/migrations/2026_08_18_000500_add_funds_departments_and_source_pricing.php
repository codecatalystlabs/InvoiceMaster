<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteen_items', function (Blueprint $table) {
            $table->boolean('is_priced')->default(true)->after('price');
        });

        DB::table('canteen_items')->where('type', 'food')->update([
            'is_priced' => false,
            'price' => 0,
        ]);
        DB::table('canteen_items')->whereIn('name', ['Greens (dodo)', 'Cabbage', 'Mixed vegetables'])->update([
            'type' => 'food',
            'is_priced' => false,
            'price' => 0,
        ]);
        DB::table('canteen_items')->whereIn('type', ['sauce', 'drink', 'extra'])->update(['is_priced' => true]);

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::create('annual_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('title');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['department_id', 'year']);
        });

        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_budget_id')->constrained('annual_budgets')->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 40)->default('operations');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('float_limit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('budget_allocation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('petty_cash_fund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('type', 30)->default('petty_cash');
            $table->string('status', 30)->default('submitted');
            $table->string('disbursement_method', 40)->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('accounted_at')->nullable();
            $table->text('accountability_notes')->nullable();
            $table->decimal('accounted_amount', 15, 2)->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('requisition_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->string('step', 40);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained()->cascadeOnDelete();
            $table->date('spent_on')->nullable();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        Schema::create('petty_cash_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('petty_cash_fund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->date('entry_date');
            $table->string('type', 30);
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_entries');
        Schema::dropIfExists('requisition_lines');
        Schema::dropIfExists('requisition_steps');
        Schema::dropIfExists('requisitions');
        Schema::dropIfExists('petty_cash_funds');
        Schema::dropIfExists('budget_allocations');
        Schema::dropIfExists('annual_budgets');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
        Schema::dropIfExists('departments');
        Schema::table('canteen_items', function (Blueprint $table) {
            $table->dropColumn('is_priced');
        });
    }
};
