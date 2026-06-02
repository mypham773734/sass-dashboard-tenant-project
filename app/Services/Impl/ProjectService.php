<?php 

namespace App\Services\Impl; 
use App\Services\Contracts\ProjectServiceInterface; 
use App\Models\Project; 
use App\DTOs\projects\CreateProjectDTO; 

class ProjectService implements ProjectServiceInterface{
    public function getProject($limit = 10)
    {
        return Project::paginate($limit); 
    }
    public function createProject(CreateProjectDTO $dto)
    {
        return Project::create($dto); 
    }

    public function updateProject()
    {
        throw new \Exception('Not implemented');
    }

    public function deleteProject()
    {
        throw new \Exception('Not implemented');
    }
}