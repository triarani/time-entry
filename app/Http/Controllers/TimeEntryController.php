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
        $query = TimeEntry::with(['employee', 'project', 'task', 'company']);

        if ($request->filled('company_id')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        // Default page size of 50 entries; client can pass `per_page` query param
        $perPage = $request->query('per_page', 50);
        return response()->json($query->orderByDesc('date')->paginate($perPage));
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
        $project = Project::find($validated['project_id']);
        $employee = Employee::find($validated['employee_id']);
        $employeeCompanies = $employee->companies()->pluck('companies.id')->toArray();
        if (!in_array($project->company_id, $employeeCompanies)) {
            return response()->json(['message' => 'The selected employee does not belong to the selected project\'s company.'], 422);
        }
        $validated['company_id'] = $project?->company_id;

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
                $project = \App\Models\Project::find($data['project_id']);
                $employee = \App\Models\Employee::find($data['employee_id']);
                $employeeCompanies = $employee->companies()->pluck('companies.id')->toArray();
                if (!in_array($project->company_id, $employeeCompanies)) {
                    $batchErrors[$index] = ['employee_id' => ['The selected employee does not belong to the selected project\'s company.']];
                } else {
                    $data['company_id'] = $project?->company_id;
                    $validatedEntries[] = $data;
                }
            }
        }

        if (!empty($batchErrors)) {
            // Transform errors to a keyed structure: "entries.{row}.{field}" => [messages]
            $formatted = [];
            foreach ($batchErrors as $row => $fields) {
                foreach ($fields as $field => $messages) {
                    $key = "entries.$row.$field";
                    $formatted[$key] = $messages;
                }
            }
            return response()->json(['errors' => $formatted], 422);
        }

        foreach ($validatedEntries as &$entry) {
            $entry['created_at'] = now();
            $entry['updated_at'] = now();
        }
        $created = \App\Models\TimeEntry::insert($validatedEntries);
        return response()->json(['created' => count($validatedEntries)], 201);
    }

    /**
     * Update an existing time entry.
     */
    public function update(Request $request, int $id)
    {
        $entry = TimeEntry::findOrFail($id);

        $validated = \Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'project_id'  => [
                'required',
                'exists:projects,id',
                new \App\Rules\ProjectPerDateRule(
                    $request->input('employee_id'),
                    $request->input('date') ?? $entry->date->toDateString(),
                    $id
                ),
            ],
            'task_id'     => 'required|exists:tasks,id',
            'date'        => 'nullable|date',
            'hours'       => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ])->validate();

        if (empty($validated['date'])) {
            $validated['date'] = $entry->date->toDateString();
        }
        $project = Project::find($validated['project_id']);
        $validated['company_id'] = $project?->company_id;

        $entry->update($validated);
        return response()->json($entry->fresh(['employee', 'project', 'task']));
    }

    /**
     * Delete a time entry.
     */
    public function destroy(int $id)
    {
        $entry = TimeEntry::findOrFail($id);
        $entry->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Return summary totals by employee, project, or company.
     */
    public function summary(Request $request)
    {
        $by = $request->query('by', 'employee');
        $query = TimeEntry::with(['employee', 'project']);

        if ($request->filled('company_id')) {
            $query->whereHas('project', function ($q) use ($request) {
                $q->where('company_id', $request->input('company_id'));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->input('to'));
        }

        $entries = $query->get();

        if ($by === 'company') {
            $grouped = $entries->groupBy(fn($e) => $e->project->company_id ?? 'unknown');
            $result = $grouped->map(function ($group, $companyId) {
                $company = $group->first()->project->company ?? null;
                return [
                    'id' => $companyId,
                    'name' => $company?->name ?? 'Unknown',
                    'total_hours' => $group->sum('hours'),
                ];
            })->sortByDesc('total_hours')->values();
        } elseif ($by === 'project') {
            $grouped = $entries->groupBy('project_id');
            $result = $grouped->map(function ($group, $projectId) {
                return [
                    'id' => $projectId,
                    'name' => $group->first()->project->name ?? 'Unknown',
                    'total_hours' => $group->sum('hours'),
                ];
            })->sortByDesc('total_hours')->values();
        } else {
            $grouped = $entries->groupBy('employee_id');
            $result = $grouped->map(function ($group, $employeeId) {
                return [
                    'id' => $employeeId,
                    'name' => $group->first()->employee ? 
                        $group->first()->employee->first_name . ' ' . $group->first()->employee->last_name : 'Unknown',
                    'total_hours' => $group->sum('hours'),
                ];
            })->sortByDesc('total_hours')->values();
        }

        return response()->json([
            'summary_by' => $by,
            'totals' => $result,
            'grand_total' => $entries->sum('hours'),
        ]);
    }
}
