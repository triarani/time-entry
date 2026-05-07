<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\TimeEntry;

class NoDuplicateTimeEntryRule implements ValidationRule
{
    protected $employeeId;
    protected $projectId;
    protected $taskId;
    protected $date;
    protected $excludeId;

    public function __construct($employeeId, $projectId, $taskId, $date, $excludeId = null)
    {
        $this->employeeId = $employeeId;
        $this->projectId = $projectId;
        $this->taskId = $taskId;
        $this->date = $date;
        $this->excludeId = $excludeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->employeeId || !$this->projectId || !$this->taskId || !$this->date) {
            return;
        }

        $query = TimeEntry::where('employee_id', $this->employeeId)
            ->where('project_id', $this->projectId)
            ->where('task_id', $this->taskId)
            ->where('date', $this->date);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('This employee already has an entry for this project and task on this date.');
        }
    }
}