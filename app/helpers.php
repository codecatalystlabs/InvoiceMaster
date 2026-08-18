<?php

use App\Models\Company;
use Illuminate\Support\HtmlString;

function money_text($amount, $company = null): string
{
    $symbol = 'UGX';
    $company = $company
        ?? (auth()->check() ? auth()->user()->company : null)
        ?? (function_exists('app') && app()->bound('company') && app('company') instanceof Company ? app('company') : null);
    if ($company instanceof Company) {
        $symbol = $company->currency ?: 'UGX';
    }

    return $symbol.' '.number_format((float) $amount, 0);
}

function money($amount, $company = null): HtmlString
{
    return new HtmlString('<span class="money">'.e(money_text($amount, $company)).'</span>');
}

function status_badge(string $status): string
{
    $map = [
        'Draft' => 'secondary',
        'draft' => 'secondary',
        'Sent' => 'info',
        'sent' => 'success',
        'failed' => 'danger',
        'received' => 'info',
        'read' => 'secondary',
        'incoming' => 'success',
        'outgoing' => 'primary',
        'Accepted' => 'success',
        'Rejected' => 'danger',
        'Converted' => 'primary',
        'Unpaid' => 'warning',
        'unpaid' => 'warning',
        'Partially Paid' => 'info',
        'Paid' => 'success',
        'paid' => 'success',
        'Overdue' => 'danger',
        'overdue' => 'danger',
        'Cancelled' => 'dark',
        'cancelled' => 'dark',
        'proforma' => 'primary',
        'Pending' => 'warning',
        'pending' => 'warning',
        'approved' => 'success',
        'Approved' => 'success',
        'refused' => 'danger',
        'Refused' => 'danger',
        'posted' => 'primary',
        'Posted' => 'primary',
        'submitted' => 'info',
        'initiated' => 'primary',
        'disbursed' => 'warning',
        'accounted' => 'info',
        'closed' => 'success',
        'rejected' => 'danger',
        'Active' => 'success',
        'Inactive' => 'secondary',
        'present' => 'success',
        'late' => 'warning',
        'absent' => 'danger',
        'incomplete' => 'info',
        'leave' => 'primary',
        'weekend' => 'secondary',
        'holiday' => 'secondary',
        'overtime' => 'info',
        'debit' => 'success',
        'credit' => 'danger',
        'Income' => 'success',
        'Expense' => 'danger',
    ];

    $class = $map[$status] ?? 'secondary';

    return '<span class="badge bg-'.$class.'">'.e($status).'</span>';
}

function minutes_label($minutes): string
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return '—';
    }

    return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function can_module(string $module): bool
{
    return auth()->check() && auth()->user()->canAccess($module);
}

function role_options(): array
{
    return array_keys(config('modules.roles', ['Admin' => [], 'Finance' => [], 'Sales' => [], 'Reviewer' => [], 'Staff' => []]));
}
