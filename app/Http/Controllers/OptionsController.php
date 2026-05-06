<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Provide option lists for dependent dropdowns.
 * Endpoints expect a `company_id` query parameter and return the
 * corresponding collection.
 */
class OptionsController extends Controller
{
    public function employees(Request $request)
    {
        $companyId = $request->query('company_id');
        $query = Employee::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return response()->json($query->get());
    }

    public function projects(Request $request)
    {
        $companyId = $request->query('company_id');
        $query = Project::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return response()->json($query->get());
    }

    public function tasks(Request $request)
    {
        $companyId = $request->query('company_id');
        $query = Task::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return response()->json($query->get());
    }
}
