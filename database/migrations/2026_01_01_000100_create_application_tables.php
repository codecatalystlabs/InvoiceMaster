<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('Sales');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_number');
            $table->date('date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 30)->default('Draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->string('client_name')->nullable();
            $table->string('client_contact')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 30)->default('Unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->string('client_name');
            $table->string('client_contact')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 40)->default('cash');
            $table->date('issued_date');
            $table->string('reference_no')->nullable();
            $table->string('balance')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('account_code', 20);
            $table->string('account_name');
            $table->string('account_type', 30);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->date('entry_date');
            $table->string('description');
            $table->string('folio')->nullable();
            $table->decimal('discount_allowed', 15, 2)->default(0);
            $table->string('type', 10); // debit | credit
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('payment_method', 40)->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('expense_number');
            $table->date('expense_date');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('vendor_name');
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 40)->default('Cash');
            $table->string('payment_status', 30)->default('Pending');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_frequency', 30)->nullable();
            $table->date('next_recurrence_date')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('asset_number');
            $table->string('asset_name');
            $table->string('category');
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('current_value', 15, 2);
            $table->decimal('depreciation_rate', 8, 2)->default(0);
            $table->string('depreciation_method', 40)->default('None');
            $table->string('location')->nullable();
            $table->string('condition_status', 30)->default('Good');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('valuation_date');
            $table->decimal('valuation_amount', 15, 2);
            $table->text('valuation_reason')->nullable();
            $table->foreignId('valued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('service_number');
            $table->string('service_name');
            $table->string('provider_name');
            $table->string('provider_contact')->nullable();
            $table->string('category')->nullable();
            $table->decimal('cost', 15, 2);
            $table->string('billing_frequency', 30);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date');
            $table->boolean('auto_renew')->default(true);
            $table->string('status', 30)->default('Active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 40);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('reference_number');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->string('entry_type', 10); // Debit | Credit
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('message_id')->nullable();
            $table->string('reference_type', 30)->default('general');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('direction', 20)->default('outgoing');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->text('to_email');
            $table->string('subject', 500);
            $table->longText('body_html')->nullable();
            $table->string('status', 20)->default('sent');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('service_payments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('asset_valuations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_book_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('invitations');
    }
};
