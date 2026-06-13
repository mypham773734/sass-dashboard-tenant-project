<?php 

namespace App\Domain\User\Enums;


enum RoleEnum: string
{
    case SYSTEM_ADMIN = 'system_admin';
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
}