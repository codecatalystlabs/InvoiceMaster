<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('client')->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('projects.index', compact('projects', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'client_id' => 'nullable|exists:clients,id',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:Active,On hold,Closed',
            'notes' => 'nullable|string',
        ]);
        $data['code'] = DocumentNumber::next('PRJ', 'projects', 'code', auth()->user()->company_id);
        Project::create($data);

        return back()->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $project->load('client');
        $invoiced = (float) $project->invoices()->sum('total');
        $costs = (float) $project->expenses()->sum('amount') + (float) $project->bills()->sum('total');

        return view('projects.show', compact('project', 'invoiced', 'costs'));
    }

    public function create()
    {
        return view('projects.index', [
            'projects' => Project::with('client')->orderBy('name')->get(),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }
}
