<?php 

namespace App\Services\Impl;

use App\Services\Contracts\EnglishEgentServiceInterface; 
use App\Ai\Agents\EnglishEgent; 
use App\DTOs\englishs\PromptGenerateMessageDTO; 

class EnglishAgentService implements EnglishEgentServiceInterface{
    public function generateMessage(EnglishEgent $englishEgent, PromptGenerateMessageDTO $promptGenerateMessageDTO){
        $prompt = $promptGenerateMessageDTO->toArray()['prompt']; 
        return $englishEgent->prompt($prompt); 
    }

}