<?php 

namespace App\DTOs\projects; 

class CreateProjectDTO{
    public function __construct(
        public readonly string $tenant_id, 
        public readonly string $name,  
        public readonly string $description, 
        public readonly bool $status
    )
    {}

    public static function fromArray(array $data){
        return new self(
            tenant_id: $data['tenant_id'], 
            name: $data['name'], 
            description: $data['description'], 
            status: $data['status'], 
        ); 
    }

    public function toArray(){
        return [
            'tenant_id' => $this->tenant_id, 
            'name' => $this->name, 
            'description' => $this->description, 
            'status' => $this->status, 
        ]; 
    }
}