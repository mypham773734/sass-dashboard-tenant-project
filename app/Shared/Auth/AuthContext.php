<?php 

namespace App\Shared\Auth;

use App\Models\User; 

class AuthContext{
    public function getUser():User{
        return auth()->user(); 
    }

    public function getId():int{
        return auth()->id(); 
    }

    public function checkLogin():bool{
        return auth()->check(); 
    }
}