<?php

namespace Tests\Feature;

use App\Models\AttendanceDay;
use App\Models\AttendanceDevice;
use App\Models\AttendancePunch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\AttendanceService;
use App\Support\LeaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_punches_pair_into_a_day(): void
    {
        [$company, $employee, $device] = $this->seedHr();
        $rows = AttendanceService::parseAttLog("7\t2026-06-30 08:02:00\t0\t1\n7\t2026-06-30 17:10:00\t1\t1\n");
        $saved = AttendanceService::ingest($company->id, $rows, $device, 'iclock');

        $this->assertSame(2, $saved);
        $this->assertSame(2, AttendancePunch::withoutGlobalScopes()->count());
        $day = AttendanceDay::withoutGlobalScopes()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($day);
        $this->assertSame('08:02', $day->clock_in->format('H:i'));
        $this->assertSame('17:10', $day->clock_out->format('H:i'));
        $this->assertSame('present', $day->status);
    }

    public function test_iclock_endpoint_accepts_attlog(): void
    {
        [, $employee, $device] = $this->seedHr();
        $this->call('POST', '/iclock/cdata?SN='.$device->serial_number.'&table=ATTLOG', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], "7\t2026-06-30 08:05:00\t0\t1\n")->assertOk();

        $this->postJson('/api/v1/attendance/punches', [
            'device_key' => $device->device_key,
            'punches' => [[
                'pin' => '7',
                'punched_at' => '2026-06-30 17:02:00',
                'status' => 1,
            ]],
        ])->assertOk()->assertJsonPath('saved', 1);

        $this->assertTrue(
            AttendancePunch::withoutGlobalScopes()->where('employee_id', $employee->id)->exists()
        );
    }

    public function test_unpaid_leave_counts_working_days(): void
    {
        [$company, $employee] = $this->seedHr();
        LeaveType::seedDefaults($company->id);
        $type = LeaveType::withoutGlobalScopes()->where('company_id', $company->id)->where('code', 'UNPAID')->first();
        LeaveRequest::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-02',
            'days' => 2,
            'status' => 'approved',
        ]);

        $this->assertSame(2.0, LeaveService::unpaidDays($employee, 2026, 6));
    }

    protected function seedHr(): array
    {
        $company = Company::create(['name' => 'HR Co', 'currency' => 'UGX']);
        $employee = Employee::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'number' => 'EMP-7',
            'name' => 'Jane Clock',
            'machine_pin' => '7',
            'basic_salary' => 1000000,
            'allowances' => 0,
            'pay_method' => 'bank',
            'status' => 'Active',
        ]);
        $device = AttendanceDevice::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Gate',
            'serial_number' => 'ZKTEST1',
            'vendor' => 'zkteco',
            'work_start' => '08:00:00',
            'work_end' => '17:00:00',
            'late_grace_minutes' => 15,
            'is_active' => true,
        ]);

        return [$company, $employee, $device];
    }
}
