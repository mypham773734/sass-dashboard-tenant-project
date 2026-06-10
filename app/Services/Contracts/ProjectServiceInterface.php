<?php 

namespace App\Services\Contracts;

use App\Application\Project\DTOs\CreateProjectDTO; 

interface ProjectServiceInterface{
    public function getProject($limit = 10); 
    public function createProject(CreateProjectDTO $dto); 

    public function updateProject(); 

    public function deleteProject(); 
}