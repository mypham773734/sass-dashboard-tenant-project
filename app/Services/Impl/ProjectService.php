<?php 

namespace App\Services\Impl; 
use App\Services\Contracts\ProjectServiceInterface; 
use App\Models\Project; 

class ProjectService implements ProjectServiceInterface{
    public function getProject($limit = 10)
    {
        return Project::paginate($limit); 
    }
    public function createProject()
    {
        throw new \Exception('Not implemented');
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