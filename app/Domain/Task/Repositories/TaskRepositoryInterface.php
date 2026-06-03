<?php 

namespace App\Domain\Task\Repositories;

interface TaskRepositoryInterface
{
    public function findById();
    public function create();
    public function update();
    public function delete();
}