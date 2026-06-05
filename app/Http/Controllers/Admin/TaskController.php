<?php

namespace App\Http\Controllers\Admin;

use App\Application\Project\UseCases\GetAllProjectsUseCase;
use App\Application\Task\DTOs\CreateTaskDTO;
use App\Application\Task\DTOs\UpdateTaskDTO;
use App\Application\Task\UseCases\CreateTaskUseCase;
use App\Application\Task\UseCases\DeleteTaskUseCase;
use App\Application\Task\UseCases\FindTaskByIdUseCase;
use App\Application\Task\UseCases\GetTasksUseCase;
use App\Application\Task\UseCases\UpdateTaskUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskController extends Controller
{
    public function __construct(
        private readonly GetTasksUseCase      $getTasksUseCase,
        private readonly FindTaskByIdUseCase  $findTaskByIdUseCase,
        private readonly CreateTaskUseCase    $createTaskUseCase,
        private readonly UpdateTaskUseCase    $updateTaskUseCase,
        private readonly DeleteTaskUseCase    $deleteTaskUseCase,
        private readonly GetAllProjectsUseCase $getAllProjectsUseCase,
    ) {}

    public function index()
    {
        try {
            $tenantId = session('current_tenant_id');
            $this->authorize('viewAny', [Task::class, $tenantId]);

            $tasks = $this->getTasksUseCase->execute($tenantId);

            return view('admin.pages.task.index', compact('tasks'));
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load tasks.');
        }
    }

    public function create()
    {
        try {
            $tenantId = session('current_tenant_id');
            $this->authorize('create', [Task::class, $tenantId]);

            $projects = $this->getAllProjectsUseCase->execute($tenantId);

            return view('admin.pages.task.create', compact('projects'));
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function store(StoreTaskRequest $request)
    {
        try {
            $tenantId = session('current_tenant_id');
            $this->authorize('create', [Task::class, $tenantId]);

            $dto = CreateTaskDTO::fromArray($request->validated());
            $this->createTaskUseCase->execute(
                dto:       $dto,
                tenantId:  $tenantId,
                createdBy: auth()->id(),
            );

            return redirect()
                ->route('task.index')
                ->with('success', 'Task created successfully.');
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to create task.')->withInput();
        }
    }

    public function edit(int $id)
    {
        try {
            $tenantId = session('current_tenant_id');
            $task     = $this->findTaskByIdUseCase->execute($id, $tenantId);

            if (! $task) {
                abort(404);
            }

            $this->authorize('update', new Task([
                'tenant_id'   => $task->tenantId,
                'created_by'  => $task->createdBy,
                'assignee_id' => $task->assigneeId,
            ]));

            $projects = $this->getAllProjectsUseCase->execute($tenantId);

            return view('admin.pages.task.create', compact('task', 'projects'));
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load task.');
        }
    }

    public function update(UpdateTaskRequest $request, int $id)
    {
        try {
            $tenantId = session('current_tenant_id');
            $existing = $this->findTaskByIdUseCase->execute($id, $tenantId);

            if (! $existing) {
                abort(404);
            }

            $this->authorize('update', new Task([
                'tenant_id'   => $existing->tenantId,
                'created_by'  => $existing->createdBy,
                'assignee_id' => $existing->assigneeId,
            ]));

            $dto = UpdateTaskDTO::fromArray($request->validated());
            $this->updateTaskUseCase->execute(
                id:       $id,
                tenantId: $tenantId,
                dto:      $dto,
            );

            return redirect()
                ->route('task.index')
                ->with('success', 'Task updated successfully.');
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to update task.')->withInput();
        }
    }

    public function destroy(int $id)
    {
        try {
            $tenantId = session('current_tenant_id');
            $task     = $this->findTaskByIdUseCase->execute($id, $tenantId);

            if (! $task) {
                abort(404);
            }

            $this->authorize('delete', new Task([
                'tenant_id'  => $task->tenantId,
                'created_by' => $task->createdBy,
            ]));

            $this->deleteTaskUseCase->execute($id, $tenantId);

            return redirect()
                ->route('task.index')
                ->with('success', 'Task deleted successfully.');
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to delete task.');
        }
    }
}
