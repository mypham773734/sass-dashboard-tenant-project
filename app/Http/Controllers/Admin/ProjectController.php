<?php

namespace App\Http\Controllers\Admin;

use App\Application\Project\DTOs\CreateProjectDTO;
use App\Application\Project\DTOs\UpdateProjectDTO;
use App\Application\Project\UseCases\CreateProjectUseCase;
use App\Application\Project\UseCases\DeleteProjectUseCase;
use App\Application\Project\UseCases\FindProjectByIdUseCase;
use App\Application\Project\UseCases\GetAllProjectsUseCase;
use App\Application\Project\UseCases\UpdateProjectUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function __construct(
        private readonly GetAllProjectsUseCase   $getAllProjectsUseCase,
        private readonly FindProjectByIdUseCase  $findProjectByIdUseCase,
        private readonly CreateProjectUseCase    $createProjectUseCase,
        private readonly UpdateProjectUseCase    $updateProjectUseCase,
        private readonly DeleteProjectUseCase    $deleteProjectUseCase,
    ) {}

    public function index()
    {
        try {
            $tenantId = session('current_tenant_id');
            $projects = $this->getAllProjectsUseCase->execute($tenantId);

            return view('admin.pages.project.index', compact('projects'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load projects.');
        }
    }

    public function create()
    {
        try {
            return view('admin.pages.project.create');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function store(StoreProjectRequest $request)
    {
        try {
            $dto = CreateProjectDTO::fromArray($request->validated());
            $this->createProjectUseCase->execute(
                dto:      $dto,
                tenantId: session('current_tenant_id'),
                ownerId:  auth()->id(),
            );

            return redirect()
                ->route('project.index')
                ->with('success', 'Project created successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to create project.')->withInput();
        }
    }

    public function show(int $id)
    {
        try {
            $project = $this->findProjectByIdUseCase->execute($id, session('current_tenant_id'));

            if (! $project) {
                abort(404);
            }

            return view('admin.pages.project.show', compact('project'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load project.');
        }
    }

    public function edit(int $id)
    {
        try {
            $project = $this->findProjectByIdUseCase->execute($id, session('current_tenant_id'));

            if (! $project) {
                abort(404);
            }

            return view('admin.pages.project.create', compact('project'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load project.');
        }
    }

    public function update(UpdateProjectRequest $request, int $id)
    {
        try {
            $dto = UpdateProjectDTO::fromArray($request->validated());
            $this->updateProjectUseCase->execute(
                id:       $id,
                tenantId: session('current_tenant_id'),
                dto:      $dto,
            );

            return redirect()
                ->route('project.index')
                ->with('success', 'Project updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to update project.')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->deleteProjectUseCase->execute($id, session('current_tenant_id'));

            return redirect()
                ->route('project.index')
                ->with('success', 'Project deleted successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to delete project.');
        }
    }
}
