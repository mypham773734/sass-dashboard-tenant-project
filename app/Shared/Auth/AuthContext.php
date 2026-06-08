<?php 

namespace App\Shared\Auth;

class AuthContext{
    public function getUser(){
        return auth()->user(); 
    }

    public function getId():int{
        return auth()->id(); 
    }

    public function checkLogin(){
        
    }
}