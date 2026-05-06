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
        // Basic field validation with custom rule
        $validated = \Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'project_id'  => [
                'required',
                'exists:projects,id',
                new \App\Rules\ProjectPerDateRule(
                    $request->input('employee_id'),
                    $request->input('date') ?? now()->toDateString()
                ),
            ],
            // Tasks are company‑wide, not project‑specific.
            'task_id'     => 'required|exists:tasks,id',
            'date'        => 'nullable|date',
            'hours'       => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ])->validate();

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
                'project_id'  => [
                    'required',
                    'exists:projects,id',
                    new \App\Rules\ProjectPerDateRule(
                        $entry['employee_id'],
                        $entry['date'] ?? now()->toDateString()
                    ),
                ],
                'task_id'     => 'required|exists:tasks,id',
                'date'        => 'nullable|date',
                'hours'       => 'required|numeric|min:0',
                'notes'       => 'nullable|string',
            ]);

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
            // Transform errors to a flat array that includes the row index, field, and message.
            $formatted = [];
            foreach ($batchErrors as $row => $fields) {
                foreach ($fields as $field => $messages) {
                    foreach ($messages as $msg) {
                        $formatted[] = [
                            'row' => $row,
                            'field' => $field,
                            'message' => $msg,
                        ];
                    }
                }
            }
            return response()->json(['errors' => $formatted], 422);
        }

        $created = \App\Models\TimeEntry::insert($validatedEntries);
        return response()->json(['created' => count($validatedEntries)], 201);
    }
}
