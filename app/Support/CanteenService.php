<?php

namespace App\Support;

use App\Models\CanteenItem;
use App\Models\CanteenMeal;
use App\Models\CanteenMonthClose;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\User;
use App\Support\LedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CanteenService
{
    public static function selectedLines(array $input): array
    {
        $selected = [];
        foreach ($input['item_ids'] ?? [] as $id) {
            $qty = (float) ($input['qty'][$id] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $selected[(int) $id] = $qty;
        }

        return $selected;
    }

    public static function submit(User $user, Carbon $date, array $selected, ?string $notes): CanteenMeal
    {
        if ($selected === []) {
            throw ValidationException::withMessages([
                'item_ids' => 'Select the source you ordered (chicken, beans, and so on). Foods that came with it are included in that price.',
            ]);
        }

        $priced = CanteenItem::query()
            ->whereIn('id', array_keys($selected))
            ->where('is_priced', true)
            ->where('is_active', true)
            ->exists();

        if (! $priced) {
            throw ValidationException::withMessages([
                'item_ids' => 'Pick at least one priced source. Accompaniment foods do not carry a charge.',
            ]);
        }

        return DB::transaction(function () use ($user, $date, $selected, $notes) {
            $meal = CanteenMeal::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->whereDate('meal_date', $date->toDateString())
                ->first();

            if ($meal && in_array($meal->status, ['pending', 'approved', 'posted'], true)) {
                throw ValidationException::withMessages([
                    'meal' => 'This day’s entry is locked. Ask a reviewer to approve an edit.',
                ]);
            }

            if (! $meal) {
                $meal = new CanteenMeal([
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'meal_date' => $date->toDateString(),
                ]);
            }

            $meal->fill([
                'status' => 'pending',
                'did_not_eat' => false,
                'notes' => $notes,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]);
            $meal->save();
            $isNew = $meal->wasRecentlyCreated;
            $total = self::syncLines($meal, $selected);
            $meal->update(['total' => $total]);

            Audit::log(
                $isNew ? 'Declare' : 'Resubmit',
                'CanteenMeal',
                $meal->id,
                $user->name.' declared '.$date->toDateString().' · '.money_text($total),
                $total,
                ['module' => 'canteen']
            );

            return $meal->fresh('lines');
        });
    }

    public static function syncLines(CanteenMeal $meal, array $selected): float
    {
        $meal->lines()->delete();
        $total = 0.0;

        if ($selected === []) {
            return $total;
        }

        $items = CanteenItem::query()
            ->whereIn('id', array_keys($selected))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        foreach ($selected as $itemId => $qty) {
            $item = $items->get($itemId);
            if (! $item) {
                continue;
            }
            $unit = $item->is_priced ? (float) $item->price : 0.0;
            $lineTotal = round(((float) $qty) * $unit, 2);
            $meal->lines()->create([
                'canteen_item_id' => $item->id,
                'item_name' => $item->name,
                'item_type' => $item->type,
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
            ]);
            $total += $lineTotal;
        }

        return $total;
    }

    public static function applyPayload(CanteenMeal $meal, array $payload): CanteenMeal
    {
        $selected = [];
        foreach ($payload['items'] ?? [] as $row) {
            if (! empty($row['item_id'])) {
                $selected[(int) $row['item_id']] = (float) ($row['qty'] ?? 1);
            }
        }

        $meal->did_not_eat = false;
        $meal->notes = $payload['notes'] ?? $meal->notes;
        $total = self::syncLines($meal, $selected);
        $meal->total = $total;
        $meal->status = 'pending';
        $meal->reviewed_by = null;
        $meal->reviewed_at = null;
        $meal->review_notes = null;
        $meal->submitted_at = now();
        $meal->save();

        return $meal->fresh('lines');
    }

    public static function closeMonth(User $user, int $year, int $month): CanteenMonthClose
    {
        $existing = CanteenMonthClose::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'month' => 'That month is already closed.',
            ]);
        }

        return DB::transaction(function () use ($user, $year, $month) {
            $meals = CanteenMeal::with('user')
                ->whereYear('meal_date', $year)
                ->whereMonth('meal_date', $month)
                ->where('status', 'approved')
                ->whereNull('expense_id')
                ->get();

            $total = (float) $meals->sum('total');
            $account = ChartOfAccount::query()->where('account_code', '5160')->first();
            $label = Carbon::createFromDate($year, $month, 1)->format('F Y');
            $expense = Expense::create([
                'expense_number' => DocumentNumber::next('EXP', 'expenses', 'expense_number', $user->company_id),
                'expense_date' => Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString(),
                'account_id' => $account?->id,
                'vendor_name' => 'Staff canteen',
                'category' => 'Canteen',
                'amount' => $total,
                'payment_method' => 'Cash',
                'payment_status' => 'Pending',
                'description' => 'Approved canteen meals for '.$label.' ('.$meals->count().' entries)',
                'source_type' => 'CanteenMonth',
                'created_by' => $user->id,
            ]);

            foreach ($meals as $meal) {
                $meal->update(['status' => 'posted', 'expense_id' => $expense->id]);
            }

            $close = CanteenMonthClose::create([
                'year' => $year,
                'month' => $month,
                'expense_id' => $expense->id,
                'total' => $total,
                'meal_count' => $meals->count(),
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);
            $expense->update(['source_id' => $close->id]);
            LedgerService::postExpense($expense->fresh());

            Audit::log('CloseMonth', 'CanteenMonth', $close->id, $label.' posted as '.$expense->expense_number.' · '.money_text($total), $total, ['module' => 'canteen.close']);

            return $close->load('expense');
        });
    }
}
