<?php 

namespace App\Services\Contracts; 

interface ProjectServiceInterface{
    public function getProject($limit = 10); 
    public function createProject(); 

    public function updateProject(); 

    public function deleteProject(); 
}