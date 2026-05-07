<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Provide option lists for dependent dropdowns.
 * Endpoints expect a `company_id` query parameter and return the
 * corresponding collection.
 */
class OptionsController extends Controller
{
    /**
     * Return company options, cached for performance.
     */
    public function companies()
    {
        $companies = Cache::remember('options:companies:all', now()->addMinutes(60), function () {
            return Company::all();
        });
        return response()->json($companies);
    }

    /**
     * Return employee options, filtered by company.
     * Cache key includes the optional company_id filter.
     */
    public function employees(Request $request)
    {
        $companyId = $request->query('company_id');
        $cacheKey = $companyId ? "options:employees:company:$companyId" : 'options:employees:all';
        $employees = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($companyId) {
            $query = Employee::query();
            if ($companyId) {
                $query->whereHas('companies', function ($q) use ($companyId) {
                    $q->where('companies.id', $companyId);
                });
            }
            return $query->get();
        });
        return response()->json($employees);
    }

    /**
     * Return project options, cached for performance.
     */
    public function projects(Request $request)
    {
        $companyId = $request->query('company_id');
        $cacheKey = $companyId ? "options:projects:company:$companyId" : 'options:projects:all';
        $projects = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($companyId) {
            $query = Project::query();
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            return $query->get();
        });
        return response()->json($projects);
    }

    /**
     * Return task options, cached for performance.
     */
    public function tasks(Request $request)
    {
        $companyId = $request->query('company_id');
        $cacheKey = $companyId ? "options:tasks:company:$companyId" : 'options:tasks:all';
        $tasks = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($companyId) {
            $query = Task::query();
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            return $query->get();
        });
        return response()->json($tasks);
    }
}
