<?php

namespace App\Support;

use App\Models\AttendanceDay;
use App\Models\AttendanceDevice;
use App\Models\AttendancePunch;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceService
{
    public static function parseAttLog(string $body): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', trim($body)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\t+/', $line);
            if (count($parts) < 2) {
                $parts = preg_split('/\s{2,}|\s/', $line);
            }
            if (count($parts) < 2) {
                continue;
            }
            $pin = trim((string) $parts[0]);
            $when = self::parsePunchTime($parts[1].(isset($parts[2]) && preg_match('/^\d{2}:\d{2}/', (string) $parts[2]) ? ' '.$parts[2] : ''));
            if ($pin === '' || ! $when) {
                continue;
            }
            $statusIndex = preg_match('/^\d{2}:\d{2}/', (string) ($parts[2] ?? '')) ? 3 : 2;
            $rows[] = [
                'machine_pin' => $pin,
                'punched_at' => $when,
                'status' => (int) ($parts[$statusIndex] ?? 0),
                'verify' => isset($parts[$statusIndex + 1]) ? (int) $parts[$statusIndex + 1] : null,
            ];
        }

        return $rows;
    }

    public static function ingest(int $companyId, array $punches, ?AttendanceDevice $device = null, string $source = 'machine'): int
    {
        $saved = 0;
        $dates = [];
        foreach ($punches as $punch) {
            $when = $punch['punched_at'] instanceof CarbonInterface
                ? $punch['punched_at']
                : self::parsePunchTime((string) $punch['punched_at']);
            if (! $when) {
                continue;
            }
            $pin = trim((string) ($punch['machine_pin'] ?? $punch['pin'] ?? ''));
            if ($pin === '') {
                continue;
            }
            $employee = self::findEmployee($companyId, $pin);
            $row = AttendancePunch::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'machine_pin' => $pin,
                    'punched_at' => $when->format('Y-m-d H:i:s'),
                    'status' => (int) ($punch['status'] ?? 0),
                ],
                [
                    'attendance_device_id' => $device?->id,
                    'employee_id' => $employee?->id,
                    'verify' => $punch['verify'] ?? null,
                    'source' => $source,
                ]
            );
            if ($row->wasRecentlyCreated) {
                $saved++;
            } elseif (! $row->employee_id && $employee) {
                $row->update(['employee_id' => $employee->id]);
            }
            if ($employee) {
                $dates[$employee->id][$when->toDateString()] = true;
            }
        }
        foreach ($dates as $employeeId => $days) {
            foreach (array_keys($days) as $date) {
                self::rebuildDay($companyId, (int) $employeeId, $date, $device);
            }
        }

        return $saved;
    }

    public static function rebuildDay(int $companyId, int $employeeId, string $date, ?AttendanceDevice $device = null): AttendanceDay
    {
        $day = Carbon::parse($date)->startOfDay();
        $punches = AttendancePunch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('punched_at', $day->toDateString())
            ->orderBy('punched_at')
            ->get();

        $clockIn = self::firstIn($punches) ?? $punches->first()?->punched_at;
        $clockOut = self::lastOut($punches) ?? ($punches->count() > 1 ? $punches->last()?->punched_at : null);

        $workStart = Carbon::parse($day->toDateString().' '.($device?->work_start ?? '08:00:00'));
        $grace = (int) ($device?->late_grace_minutes ?? 15);
        $late = 0;
        if ($clockIn && $clockIn->gt($workStart->copy()->addMinutes($grace))) {
            $late = (int) $workStart->diffInMinutes($clockIn);
        }
        $worked = ($clockIn && $clockOut && $clockOut->gt($clockIn)) ? (int) $clockIn->diffInMinutes($clockOut) : 0;

        $status = self::dayStatus($companyId, $employeeId, $day, $clockIn, $clockOut);
        $values = [
            'company_id' => $companyId,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'worked_minutes' => $worked,
            'late_minutes' => $late,
            'status' => $status,
        ];
        $row = AttendanceDay::withoutGlobalScopes()
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $day->toDateString())
            ->first();
        if ($row) {
            $row->update($values);

            return $row;
        }

        return AttendanceDay::withoutGlobalScopes()->create($values + [
            'employee_id' => $employeeId,
            'work_date' => $day->toDateString(),
        ]);
    }

    public static function rebuildRange(int $companyId, string $from, string $to, ?AttendanceDevice $device = null): int
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();
        $count = 0;
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        while ($cursor->lte($end)) {
            foreach ($employees as $employee) {
                self::rebuildDay($companyId, $employee->id, $cursor->toDateString(), $device);
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    public static function isWorkday(int $companyId, CarbonInterface $day): bool
    {
        if ($day->isWeekend()) {
            return false;
        }

        return ! Holiday::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('holiday_date', $day->toDateString())
            ->exists();
    }

    public static function workingDaysBetween(int $companyId, CarbonInterface $from, CarbonInterface $to): int
    {
        $days = 0;
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            if (self::isWorkday($companyId, $cursor)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    public static function findEmployee(int $companyId, string $pin): ?Employee
    {
        return Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($pin) {
                $q->where('machine_pin', $pin)
                    ->orWhere('number', $pin)
                    ->orWhere('number', 'EMP-'.$pin)
                    ->orWhere('id', $pin);
            })
            ->first();
    }

    protected static function firstIn(Collection $punches): ?Carbon
    {
        $in = $punches->first(fn (AttendancePunch $p) => in_array((int) $p->status, [0, 4], true));

        return $in?->punched_at;
    }

    protected static function lastOut(Collection $punches): ?Carbon
    {
        $out = $punches->last(fn (AttendancePunch $p) => in_array((int) $p->status, [1, 5], true));

        return $out?->punched_at;
    }

    protected static function dayStatus(int $companyId, int $employeeId, Carbon $day, $clockIn, $clockOut): string
    {
        $onLeave = LeaveRequest::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->exists();
        if ($onLeave) {
            return 'leave';
        }
        if (! self::isWorkday($companyId, $day)) {
            return $clockIn ? 'overtime' : ($day->isWeekend() ? 'weekend' : 'holiday');
        }
        if (! $clockIn) {
            return 'absent';
        }
        if (! $clockOut) {
            return 'incomplete';
        }

        $device = AttendanceDevice::withoutGlobalScopes()->where('company_id', $companyId)->where('is_active', true)->first();
        $workStart = Carbon::parse($day->toDateString().' '.($device?->work_start ?? '08:00:00'));
        $grace = (int) ($device?->late_grace_minutes ?? 15);
        if ($clockIn->gt($workStart->copy()->addMinutes($grace))) {
            return 'late';
        }

        return 'present';
    }

    protected static function parsePunchTime(string $value): ?Carbon
    {
        $value = trim($value);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $value);
                if ($dt !== false) {
                    return $dt;
                }
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
