<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Company;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->info('No companies found. Please run CompanySeeder first.');
            return;
        }

        $tasks = [
            ['name' => 'Development', 'description' => 'Software development tasks'],
            ['name' => 'Design', 'description' => 'UI/UX design work'],
            ['name' => 'Testing', 'description' => 'Quality assurance and testing'],
            ['name' => 'Deployment', 'description' => 'Deployment and maintenance'],
            ['name' => 'Documentation', 'description' => 'Documentation and reporting'],
            ['name' => 'Meeting', 'description' => 'Internal meetings and discussions'],
            ['name' => 'Research', 'description' => 'Research and planning'],
            ['name' => 'Support', 'description' => 'Customer support and issues'],
            ['name' => 'Training', 'description' => 'Team training and onboarding'],
            ['name' => 'Code Review', 'description' => 'Code review and pair programming'],
        ];

        foreach ($companies as $company) {
            foreach ($tasks as $task) {
                Task::create([
                    'company_id' => $company->id,
                    'name' => $task['name'],
                    'description' => $task['description'],
                ]);
            }
        }

        $this->command->info('Task seeding completed.');
    }
}
