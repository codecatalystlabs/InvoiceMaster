<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('national_id', 40)->nullable()->after('date_of_birth');
            $table->string('address')->nullable()->after('phone');
            $table->string('next_of_kin')->nullable()->after('nssf_number');
            $table->string('next_of_kin_phone', 40)->nullable()->after('next_of_kin');
            $table->string('employment_type', 30)->default('permanent')->after('job_title');
            $table->date('end_date')->nullable()->after('start_date');
            $table->foreignId('supervisor_id')->nullable()->after('department_id')->constrained('employees')->nullOnDelete();
            $table->string('machine_pin', 40)->nullable()->after('pay_account');
            $table->string('bank_name', 80)->nullable()->after('pay_method');
            $table->index(['company_id', 'machine_pin']);
        });

        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('device_key', 64)->unique();
            $table->string('vendor', 40)->default('zkteco');
            $table->string('location')->nullable();
            $table->time('work_start')->default('08:00:00');
            $table->time('work_end')->default('17:00:00');
            $table->unsignedSmallInteger('late_grace_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'serial_number']);
        });

        Schema::create('attendance_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('machine_pin', 40);
            $table->dateTime('punched_at');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('verify')->nullable();
            $table->string('source', 20)->default('machine');
            $table->timestamps();
            $table->unique(['company_id', 'machine_pin', 'punched_at', 'status'], 'att_punch_unique');
            $table->index(['company_id', 'employee_id', 'punched_at']);
        });

        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->string('status', 20)->default('absent');
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['company_id', 'work_date', 'status']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('name');
            $table->timestamps();
            $table->unique(['company_id', 'holiday_date']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->boolean('paid')->default(true);
            $table->unsignedSmallInteger('days_per_year')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled', 8, 2)->default(0);
            $table->decimal('taken', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 8, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        foreach (Company::query()->pluck('id') as $companyId) {
            LeaveType::seedDefaults((int) $companyId);
            foreach (Employee::withoutGlobalScopes()->where('company_id', $companyId)->get() as $employee) {
                LeaveType::seedBalanceFor($employee);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('attendance_days');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('attendance_devices');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn([
                'gender', 'date_of_birth', 'national_id', 'address', 'next_of_kin',
                'next_of_kin_phone', 'employment_type', 'end_date', 'machine_pin', 'bank_name',
            ]);
        });
    }
};
