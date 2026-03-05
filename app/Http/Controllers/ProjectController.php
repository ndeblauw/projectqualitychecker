<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function show(Request $request, Project $project): View
    {
        abort_if($project->user_id !== $request->user()->id, 404);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        abort_if($project->user_id !== $request->user()->id, 404);

        $project->update($request->validated());

        return to_route('projects.show', $project)->with('status', 'Project updated successfully.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        abort_if($project->user_id !== $request->user()->id, 404);

        $project->delete();

        return to_route('dashboard')->with('status', 'Project deleted successfully.');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $request->user()->projects()->create($request->validated());

        return to_route('dashboard')->with('status', 'Project added successfully.');
    }
}
