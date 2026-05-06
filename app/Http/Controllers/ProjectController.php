<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('company');
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        return response()->json($query->get());
    }
}
