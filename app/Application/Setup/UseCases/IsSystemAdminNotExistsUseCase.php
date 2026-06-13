<?php 

namespace App\Application\Setup\UseCases;

use App\Domain\User\Repositories\UserRepositoryInterface; 

class IsSystemAdminNotExistsUseCase{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ){}

    public function execute():bool{
        return $this->userRepository->getSystemAdmin() ? true : false; 
    }
}