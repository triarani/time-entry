<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with('company');
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        return response()->json($query->get());
    }
}
