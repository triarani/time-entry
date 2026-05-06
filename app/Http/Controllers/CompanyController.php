<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json(Company::all());
    }

    /**
     * Return employees that belong to the given company.
     */
    public function employees($companyId)
    {
        $employees = \App\Models\Employee::where('company_id', $companyId)->get();
        return response()->json($employees);
    }

    /**
     * Return projects that belong to the given company.
     */
    public function projects($companyId)
    {
        $projects = \App\Models\Project::where('company_id', $companyId)->get();
        return response()->json($projects);
    }

    /**
     * Return all tasks for the company. Tasks are defined as company‑wide, so we simply
     * return tasks that belong to the company.
     */
    public function tasks($companyId)
    {
        $tasks = \App\Models\Task::where('company_id', $companyId)->get();
        return response()->json($tasks);
    }
}
