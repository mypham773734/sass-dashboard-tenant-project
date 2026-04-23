<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project; 
use App\Http\Requests\StoreProjectRequest; 
use App\Http\Requests\UpdateProjectRequest; 
use App\Services\Contracts\ProjectServiceInterface; 

class ProjectController extends Controller
{
    protected $projectService; 
    protected $tenantService; 

    public function __construct(ProjectServiceInterface $projectService)
    {
        $this->projectService = $projectService; 
    }

    public function index()
    {
        $projects = Project::paginate(10);
        return view('admin.pages.project.index', compact('projects'));
    }


    public function create()
    {
        return view('admin.pages.project.create');
    }

    public function store(StoreProjectRequest $request)
    {
        // Validate and store the project
        echo "store project";
    }

    public function show($id)
    {
        // Show a specific project
        echo "show project with id: " . $id;
    }

    public function edit($id)
    {
        // Edit a specific project
        echo "edit project with id: " . $id;
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        // Validate and update the project
        echo "update project with id: " . $id;
    }

    public function destroy($id)
    {
        // Delete a specific project
        echo "delete project with id: " . $id;
    }
}