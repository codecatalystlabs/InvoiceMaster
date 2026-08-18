<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public static function request(Employee $employee, array $data, ?int $userId = null): LeaveRequest
    {
        $type = LeaveType::withoutGlobalScopes()->where('company_id', $employee->company_id)->findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        if ($end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => 'End date must be on or after the start date.']);
        }
        $days = AttendanceService::workingDaysBetween((int) $employee->company_id, $start, $end);
        if ($days <= 0) {
            throw ValidationException::withMessages(['start_date' => 'That range has no working days.']);
        }
        $overlap = LeaveRequest::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'That period overlaps another leave request.']);
        }
        if ($type->paid) {
            $balance = self::balance($employee, $type, (int) $start->year);
            if ($balance->remaining() < $days) {
                throw ValidationException::withMessages(['leave_type_id' => 'Only '.$balance->remaining().' day(s) remain on '.$type->name.'.']);
            }
        }

        return LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public static function approve(LeaveRequest $request, int $userId, ?string $notes = null): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending leave can be approved.']);
        }

        return DB::transaction(function () use ($request, $userId, $notes) {
            $type = LeaveType::withoutGlobalScopes()->find($request->leave_type_id);
            if ($type?->paid) {
                $balance = self::balance($request->employee, $type, (int) $request->start_date->year);
                $balance->increment('taken', (float) $request->days);
            }
            $request->update([
                'status' => 'approved',
                'review_notes' => $notes,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);
            AttendanceService::rebuildRange(
                (int) $request->company_id,
                $request->start_date->toDateString(),
                $request->end_date->toDateString()
            );

            return $request->fresh();
        });
    }

    public static function reject(LeaveRequest $request, int $userId, ?string $notes = null): LeaveRequest
    {
        if (! in_array($request->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages(['status' => 'This leave cannot be rejected.']);
        }

        return DB::transaction(function () use ($request, $userId, $notes) {
            if ($request->status === 'approved') {
                $type = LeaveType::withoutGlobalScopes()->find($request->leave_type_id);
                if ($type?->paid) {
                    $balance = self::balance($request->employee, $type, (int) $request->start_date->year);
                    $balance->decrement('taken', min((float) $balance->taken, (float) $request->days));
                }
            }
            $request->update([
                'status' => 'rejected',
                'review_notes' => $notes,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);
            AttendanceService::rebuildRange(
                (int) $request->company_id,
                $request->start_date->toDateString(),
                $request->end_date->toDateString()
            );

            return $request->fresh();
        });
    }

    public static function unpaidDays(Employee $employee, int $year, int $month): float
    {
        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $requests = LeaveRequest::withoutGlobalScopes()
            ->with('type')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get();
        $days = 0.0;
        foreach ($requests as $request) {
            if ($request->type?->paid) {
                continue;
            }
            $start = $request->start_date->max($from);
            $end = $request->end_date->min($to);
            $days += AttendanceService::workingDaysBetween((int) $employee->company_id, $start, $end);
        }

        return $days;
    }

    public static function balance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::withoutGlobalScopes()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ],
            [
                'company_id' => $employee->company_id,
                'entitled' => $type->days_per_year,
                'taken' => 0,
            ]
        );
    }
}
