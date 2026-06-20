<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use App\Models\MenteeProfile;

class ResumeParserController extends Controller
{
    public function parse(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf|max:5120', // Max 5MB
        ]);

        try {
            $file = $request->file('resume');
            
            // 1. Extract text from PDF
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();

            // Limit text length to avoid token limits (first 10000 chars should be plenty for a resume)
            $text = substr($text, 0, 10000);

            if (empty(trim($text))) {
                return response()->json(['message' => 'Could not extract text from PDF. Please ensure it is a readable PDF and not an image.'], 400);
            }

            // 2. Call Gemini API
            $apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
            
            if (empty($apiKey)) {
                return response()->json(['message' => 'AI Parser is currently unavailable (Missing API Key).'], 503);
            }

            $prompt = "You are an expert tech recruiter. Extract a list of technical and soft skills from the following resume text. \n\n"
                    . "IMPORTANT: Return ONLY a JSON array of strings representing the skills. Do not include markdown formatting like ```json. Do not include any other text.\n"
                    . "Example output: [\"React\", \"JavaScript\", \"Team Leadership\", \"Communication\"]\n\n"
                    . "Resume Text:\n" . $text;

            $response = Http::post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Low temperature for more deterministic/factual extraction
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                return response()->json(['message' => 'Failed to parse resume with AI.'], 500);
            }

            $responseData = $response->json();
            $aiText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Clean up the response (sometimes it adds ```json even if told not to)
            $aiText = str_replace(['```json', '```'], '', $aiText);
            $aiText = trim($aiText);
            $skills = json_decode($aiText, true);

            if (!is_array($skills)) {
                Log::error('Gemini API returned invalid JSON: ' . $aiText);
                return response()->json(['message' => 'Failed to extract skills cleanly.'], 500);
            }

            // Normalize to a flat array of strings
            $flatSkills = [];
            if (isset($skills['skills']) && is_array($skills['skills'])) {
                $flatSkills = $skills['skills'];
            } else if (is_array($skills)) {
                foreach ($skills as $k => $v) {
                    if (is_string($v)) {
                        $flatSkills[] = $v;
                    } else if (is_array($v) && isset($v['skill'])) {
                        $flatSkills[] = $v['skill'];
                    }
                }
            }

            return response()->json([
                'message' => 'Skills successfully extracted!',
                'skills' => array_values(array_filter($flatSkills))
            ]);

        } catch (\Exception $e) {
            Log::error('Resume Parse Error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while parsing the resume.'], 500);
        }
    }
}
