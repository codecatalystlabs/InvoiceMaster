<?php

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'code']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->string('level', 20)->default('mid');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('division_id')->constrained()->nullOnDelete();
        });

        foreach (Company::query()->pluck('id') as $companyId) {
            Department::seedDefaults((int) $companyId);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
            $table->dropConstrainedForeignId('division_id');
        });
        Schema::dropIfExists('positions');
        Schema::dropIfExists('divisions');
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
