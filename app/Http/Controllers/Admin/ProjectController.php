<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

use App\Application\Project\DTOs\{
    CreateProjectDTO,
    UpdateProjectDTO,
};

use App\Application\Project\UseCases\{
    CreateProjectUseCase,
    DeleteProjectUseCase,
    FindProjectByIdUseCase,
    GetAllProjectsUseCase,
    UpdateProjectUseCase
};

use App\Http\Requests\Project\{
    StoreProjectRequest,
    UpdateProjectRequest
};

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
            $tenantId = tenantContext()->getId();
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
            $tenantId = tenantContext()->getId();
            $userId = authContext()->getId();
            $this->createProjectUseCase->execute(
                dto: $dto,
                tenantId: $tenantId,
                ownerId: $userId,
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
            $tenantId = tenantContext()->getId();
            $project = $this->findProjectByIdUseCase->execute($id, $tenantId);

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
            $tenantId = tenantContext()->getId();
            $project = $this->findProjectByIdUseCase->execute($id, $tenantId);

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
            $tenantId = tenantContext()->getId();
            $dto = UpdateProjectDTO::fromArray($request->validated());
            $this->updateProjectUseCase->execute(
                id: $id,
                tenantId: $tenantId,
                dto: $dto,
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
            $tenantId = tenantContext()->getId();
            $this->deleteProjectUseCase->execute($id, $tenantId);

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
