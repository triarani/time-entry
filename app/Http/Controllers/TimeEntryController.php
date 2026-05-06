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
    /**
     * Store a new time entry.
     *
     * Enforces company‑wide task handling (tasks are not tied to a project) and
     * ensures an employee can have only one project per date.
     */
    public function store(Request $request)
    {
        // Basic field validation
        $validator = \Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'project_id'  => 'required|exists:projects,id',
            // Tasks are company‑wide, not project‑specific.
            'task_id'     => 'required|exists:tasks,id',
            'date'        => 'nullable|date',
            'hours'       => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        // Custom rule: an employee may have only one project per date.
        $validator->after(function ($validator) use ($request) {
            $date = $request->input('date') ?? now()->toDateString();
            $existing = \App\Models\TimeEntry::where('employee_id', $request->input('employee_id'))
                ->where('date', $date)
                ->first();
            if ($existing && $existing->project_id != $request->input('project_id')) {
                $validator->errors()->add('project_id', 'An employee can only have one project per date.');
            }
        });

        $validated = $validator->validate();

        // If date is not supplied, set it to the current date.
        if (empty($validated['date'])) {
            $validated['date'] = now()->toDateString();
        }

        $timeEntry = TimeEntry::create($validated);
        return response()->json($timeEntry, 201);
    }

    /**
     * Store multiple time entries in a single request.
     *
     * Expected payload:
     *   {
     *     "entries": [ { ...single entry fields... }, ... ]
     *   }
     *
     * Validation mirrors the single‑store rules and also checks that the batch
     * does not contain conflicting employee/date/project combinations.
     */
    public function storeBatch(Request $request)
    {
        $entries = $request->input('entries', []);
        if (!is_array($entries) || empty($entries)) {
            return response()->json(['message' => 'No entries provided.'], 400);
        }

        $batchErrors = [];
        $validatedEntries = [];

        foreach ($entries as $index => $entry) {
            $validator = \Validator::make($entry, [
                'employee_id' => 'required|exists:employees,id',
                'project_id'  => 'required|exists:projects,id',
                'task_id'     => 'required|exists:tasks,id',
                'date'        => 'nullable|date',
                'hours'       => 'required|numeric|min:0',
                'notes'       => 'nullable|string',
            ]);

            $validator->after(function ($validator) use ($entry, $entries, $index) {
                $date = $entry['date'] ?? now()->toDateString();
                // Check against existing DB records
                $existing = \App\Models\TimeEntry::where('employee_id', $entry['employee_id'])
                    ->where('date', $date)
                    ->first();
                if ($existing && $existing->project_id != $entry['project_id']) {
                    $validator->errors()->add('project_id', 'An employee can only have one project per date.');
                }
                // Check within the batch for duplicate employee/date with different project
                foreach ($entries as $j => $other) {
                    if ($j === $index) continue;
                    $otherDate = $other['date'] ?? now()->toDateString();
                    if ($other['employee_id'] == $entry['employee_id'] && $otherDate == $date && $other['project_id'] != $entry['project_id']) {
                        $validator->errors()->add('project_id', 'Batch contains conflicting project for same employee/date.');
                        break;
                    }
                }
            });

            if ($validator->fails()) {
                $batchErrors[$index] = $validator->errors()->messages();
            } else {
                $data = $validator->validated();
                if (empty($data['date'])) {
                    $data['date'] = now()->toDateString();
                }
                $validatedEntries[] = $data;
            }
        }

        if (!empty($batchErrors)) {
            return response()->json(['errors' => $batchErrors], 422);
        }

        $created = \App\Models\TimeEntry::insert($validatedEntries);
        return response()->json(['created' => count($validatedEntries)], 201);
    }
}
