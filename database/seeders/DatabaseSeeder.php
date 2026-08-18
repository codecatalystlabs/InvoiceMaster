<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['email' => 'info@codecatalystug.com'],
            [
                'name' => 'Code Catalyst Labs',
                'address' => '32 Kanjokya Street, Mug One House, Kamwokya',
                'phone' => '+256 783 261162',
                'currency' => 'UGX',
                'tagline' => "BUILDING TOMORROW'S SOLUTIONS TODAY",
                'services_line' => "Computer Systems, Development, Management, Maintenance\nAdministration And Consultive Support",
                'phone' => '+256 773 078860, +256 783261162',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@codecatalystug.com'],
            [
                'company_id' => $company->id,
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'Admin',
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'reviewer@codecatalystug.com'],
            [
                'company_id' => $company->id,
                'name' => 'Canteen Reviewer',
                'password' => Hash::make('review123'),
                'role' => 'Reviewer',
                'status' => 'Active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'intern@codecatalystug.com'],
            [
                'company_id' => $company->id,
                'name' => 'Intern',
                'password' => Hash::make('intern123'),
                'role' => 'Staff',
                'status' => 'Active',
            ]
        );

        ChartOfAccount::seedDefaults($company->id);
        \App\Models\CanteenItem::seedDefaults($company->id);
        \App\Models\Department::seedDefaults($company->id);
        \App\Models\LeaveType::seedDefaults($company->id);
    }
}
