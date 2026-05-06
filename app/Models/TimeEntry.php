<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $table = 'time_entries';

    // The database column is named `date`. Align the model's fillable and casts accordingly.
    protected $fillable = [
        'employee_id',
        'project_id',
        'task_id',
        'date',
        'hours',
        'notes',
    ];

    protected $casts = [
        'date'  => 'date',
        'hours' => 'decimal:2',
    ];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }
}
