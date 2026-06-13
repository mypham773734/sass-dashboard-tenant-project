<?php 

namespace App\Application\User\DTOs; 

class CreateUserDTO{
    public function __construct(
        private readonly string $name, 
        private readonly string $email, 
        private readonly string $passwordEncrypt
    ){}

    public static function fromArray(array $data):self{
        return new self(
            name : $data['name'], 
            email : $data['email'], 
            passwordEncrypt : $data['passwordEncrypt'], 
        ); 
    }

    public function toArray(){
        return [
            'name' => $this->name, 
            'email' => $this->email, 
            'password' => $this->passwordEncrypt, 
        ]; 
    }
}