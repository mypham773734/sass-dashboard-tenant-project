<?php 

namespace App\Services\Impl;

use App\Services\Contracts\EnglishEgentServiceInterface;
use App\Ai\Agents\EnglishEgent;
use App\Application\English\DTOs\PromptGenerateMessageDTO; 

class EnglishAgentService implements EnglishEgentServiceInterface{
    public function generateMessage(EnglishEgent $englishEgent, PromptGenerateMessageDTO $promptDto)
    {
        // $prompt = $promptDto->toArray()['prompt'];
        // return $englishEgent->prompt($prompt);

        return "Tôi thường đi bộ trong công viên vào mỗi buổi sáng."; 
    }

}