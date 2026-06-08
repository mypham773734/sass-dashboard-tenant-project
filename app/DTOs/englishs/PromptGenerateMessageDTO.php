<?php 

namespace App\DTOs\Englishs; 

class PromptGenerateMessageDTO{
    public function __construct(
        public readonly string $prompt
    ){

    }

    public static function fromArray(array $data): self{
        return new self(
            prompt: $data['prompt'],
        );        
    }

    public function toArray(){
        return [
            'prompt' => $this->prompt
        ]; 
    }
}