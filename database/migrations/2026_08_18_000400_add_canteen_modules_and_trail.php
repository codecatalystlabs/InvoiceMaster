<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('role');
            $table->boolean('must_declare_meals')->default(true)->after('modules');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('source_type', 40)->nullable()->after('created_by');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
        });

        Schema::create('canteen_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30)->default('food');
            $table->string('unit', 30)->default('serving');
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('canteen_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('meal_date');
            $table->string('status', 30)->default('pending');
            $table->decimal('total', 15, 2)->default(0);
            $table->boolean('did_not_eat')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'user_id', 'meal_date']);
        });

        Schema::create('canteen_meal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canteen_meal_id')->constrained('canteen_meals')->cascadeOnDelete();
            $table->foreignId('canteen_item_id')->nullable()->constrained('canteen_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('item_type', 30)->default('food');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
        });

        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 30)->default('update');
            $table->json('payload')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 50);
            $table->string('event_type', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'occurred_at']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('canteen_month_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedInteger('meal_count')->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canteen_month_closes');
        Schema::dropIfExists('transaction_events');
        Schema::dropIfExists('change_requests');
        Schema::dropIfExists('canteen_meal_lines');
        Schema::dropIfExists('canteen_meals');
        Schema::dropIfExists('canteen_items');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['modules', 'must_declare_meals']);
        });
    }
};
