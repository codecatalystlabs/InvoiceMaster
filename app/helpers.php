<?php

use App\Models\Company;

function money($amount, $company = null): string
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
        'debit' => 'success',
        'credit' => 'danger',
        'Income' => 'success',
        'Expense' => 'danger',
    ];

    $class = $map[$status] ?? 'secondary';

    return '<span class="badge bg-'.$class.'">'.e($status).'</span>';
}

function can_module(string $module): bool
{
    return auth()->check() && auth()->user()->canAccess($module);
}

function role_options(): array
{
    return array_keys(config('modules.roles', ['Admin' => [], 'Finance' => [], 'Sales' => [], 'Reviewer' => [], 'Staff' => []]));
}
