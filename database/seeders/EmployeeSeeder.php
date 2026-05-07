<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->info('No companies found. Please run CompanySeeder first.');
            return;
        }

        $employees = [
            ['first_name' => 'John', 'last_name' => 'Doe'],
            ['first_name' => 'Jane', 'last_name' => 'Smith'],
            ['first_name' => 'Mike', 'last_name' => 'Johnson'],
            ['first_name' => 'Sarah', 'last_name' => 'Williams'],
            ['first_name' => 'David', 'last_name' => 'Brown'],
        ];

        foreach ($employees as $emp) {
            $employee = Employee::create([
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => strtolower($emp['first_name'] . '.' . $emp['last_name']) . '@example.com',
            ]);
            $employee->companies()->attach($companies->pluck('id')->toArray());
        }

        $this->command->info('Employee seeding completed.');
    }
}
