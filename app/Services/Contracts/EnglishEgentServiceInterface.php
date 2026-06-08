<?php 

namespace App\Services\Contracts; 

use App\Ai\Agents\EnglishEgent; 
use App\DTOs\Englishs\PromptGenerateMessageDTO; 

interface EnglishEgentServiceInterface{
    public function generateMessage(EnglishEgent $englishEgent, PromptGenerateMessageDTO $promptDto); 
}