<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_employee');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'employee_project')
            ->withPivot('date')
            ->withTimestamps();
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
