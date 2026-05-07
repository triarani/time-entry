<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->info('No companies found. Please run CompanySeeder first.');
            return;
        }

        $projects = [
            'Website Redesign',
            'Mobile App Development',
            'API Integration',
            'Database Migration',
            'Security Audit',
            'Performance Optimization',
            'Cloud Migration',
            'E-commerce Platform',
            'Dashboard Development',
            'Customer Portal',
        ];

        foreach ($companies as $company) {
            foreach ($projects as $projectName) {
                Project::create([
                    'company_id' => $company->id,
                    'name' => $projectName,
                ]);
            }
        }

        $this->command->info('Project seeding completed.');
    }
}
