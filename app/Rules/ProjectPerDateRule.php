<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\TimeEntry;

class ProjectPerDateRule implements ValidationRule
{
    protected $employeeId;
    protected $date;
    protected $excludeId;

    public function __construct($employeeId, $date, $excludeId = null)
    {
        $this->employeeId = $employeeId;
        $this->date = $date;
        $this->excludeId = $excludeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->employeeId || !$this->date) {
            return;
        }

        $query = TimeEntry::where('employee_id', $this->employeeId)
            ->where('project_id', $value)
            ->where('date', $this->date);

        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('This employee already has an entry for this project on this date.');
        }
    }
}