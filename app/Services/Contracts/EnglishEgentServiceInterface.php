<?php 

namespace App\Services\Contracts; 

use App\Ai\Agents\EnglishEgent; 
use App\DTOs\englishs\PromptGenerateMessageDTO; 

interface EnglishEgentServiceInterface{
    public function generateMessage(EnglishEgent $englishEgent, PromptGenerateMessageDTO $promptDto); 
}