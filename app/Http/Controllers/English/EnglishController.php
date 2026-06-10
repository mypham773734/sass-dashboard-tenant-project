<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Ai\Agents\EnglishEgent;
use App\Application\English\DTOs\PromptGenerateMessageDTO;
use App\Services\Contracts\EnglishEgentServiceInterface;
use Illuminate\Http\Request;

class EnglishController extends Controller
{
    protected EnglishEgentServiceInterface $agentService;
    public function __construct(EnglishEgentServiceInterface $englishEgentService)
    {
        $this->agentService = $englishEgentService;
    }
    public function index()
    {
        $englishEgent = (new EnglishEgent);

        $level = 'A2';
        $prompt = "Generate ONE Vietnamese sentence for English learners.
            Difficulty level: $level

            Requirements:
            - Natural Vietnamese
            - Appropriate for the learner level
            - Easy to translate into English
            - No slang
            - Return only the sentence";

        $promptDto = new PromptGenerateMessageDTO($prompt);

        $message = $this->agentService->generateMessage($englishEgent, $promptDto);

        return view('english.index', ['message' => $message]);
    }

    public function generateMessage()
    {
        $englishEgent = (new EnglishEgent);

        $level = 'A2';
        $prompt = "Generate ONE Vietnamese sentence for English learners.
            Difficulty level: $level

            Requirements:
            - Natural Vietnamese
            - Appropriate for the learner level
            - Easy to translate into English
            - No slang
            - Return only the sentence";

        $promptDto = new PromptGenerateMessageDTO($prompt);

        $message = $this->agentService->generateMessage($englishEgent, $promptDto);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => []
        ], 200);
    }

    public function scoreGrammar(Request $request)
    {
        $englishEgent = (new EnglishEgent);

        $vietNam = $request->get('vietNam');
        $english = $request->get('english');
        $prompt = "You are a strict English translation scoring system.
            Evaluate how accurately the English sentence matches the Vietnamese sentence.

            Scoring criteria:
            - Meaning accuracy
            - Grammar correctness
            - Natural English usage
            - Correct tense usage

            Score range:
            0 = completely incorrect
            100 = perfect translation

            Rules:
            - Return ONLY an integer number.
            - No explanation.
            - No markdown.
            - No extra text.
            - No JSON.

            Vietnamese sentence:
           $vietNam

            English sentence:
            $english";

        $promptDto = new PromptGenerateMessageDTO($prompt);
        $score = $this->agentService->generateMessage($englishEgent, $promptDto);

        return response()->json([
            'status' => 'success',
            'message' => '',
            'data' => [
                'score' => $score
            ]
        ], 200);
    }

    public function suggetMessages(Request $request)
    {
        $englishEgent = (new EnglishEgent);

        $vietnamese = $request->get('vietNam');

        $prompt = $prompt = "
            You are an AI English writing assistant.

            The user will provide a Vietnamese sentence.

            Generate 5 different English sentence suggestions translated from the Vietnamese sentence.

            Requirements:
            - Natural English
            - Grammatically correct
            - Preserve the original meaning
            - Slight variation in wording
            - Suitable for English learners
            - Avoid overly complex vocabulary

            Rules:
            - Return ONLY the numbered list.
            - No explanations.
            - No markdown.
            - No JSON.
            - No additional text.

            Vietnamese sentence:
            {$vietnamese}
            ";
    }
}
