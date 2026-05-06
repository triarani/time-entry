<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $table = 'time_entries';

    protected $fillable = [
        'employee_id',
        'project_id',
        'task_id',
        'work_date',
        'hours',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'hours'     => 'decimal:2',
    ];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }
}
