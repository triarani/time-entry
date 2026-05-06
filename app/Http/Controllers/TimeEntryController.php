<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TimeEntryController extends Controller
{
    /**
     * Return a list of time entries, optionally filtered by company, date range, employee, project.
     */
    public function index(Request $request)
    {
        $query = TimeEntry::with(['employee', 'project', 'task']);

        if ($request->filled('company_id')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            });
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        return response()->json($query->orderByDesc('date')->get());
    }

    /**
     * Store a new time entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id'  => 'required|exists:projects,id',
            'task_id'     => 'required|exists:tasks,id',
            'date'        => 'required|date',
            'hours'       => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $timeEntry = TimeEntry::create($validated);
        return response()->json($timeEntry, 201);
    }
}
