<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeonardoService;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiGenerationController extends Controller
{
    public function intros(Request $request, OpenAiService $openAi)
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
        ]);

        $intros = $openAi->generateIntros($data['topic']);

        return response()->json([
            'intros' => $intros,
        ]);
    }

    public function slug(Request $request, OpenAiService $openAi)
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'selected_intro' => ['nullable', 'string'],
        ]);

        $slugs = $openAi->generateSlugSuggestions($data['topic'], $data['selected_intro'] ?? '');

        return response()->json([
            'slugs' => $slugs,
            'default' => $slugs[0] ?? Str::slug($data['topic']),
        ]);
    }

    public function content(Request $request, OpenAiService $openAi)
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'selected_intro' => ['required', 'string'],
        ]);

        $outline = $openAi->generateContentOutline($data['topic'], $data['selected_intro']);
        $content = $openAi->generateBodyContent($data['topic'], $data['selected_intro']);

        return response()->json([
            'outline' => $outline,
            'content' => $content,
        ]);
    }

    public function images(Request $request, LeonardoService $leonardo)
    {
        $data = $request->validate([
            'prompt' => ['nullable', 'string'],
            'topic' => ['nullable', 'string', 'max:255', 'required_without:prompt'],
            'title' => ['nullable', 'string', 'max:255'],
            'selected_intro' => ['nullable', 'string'],
            'count' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $prompts = $this->buildImagePrompts($data);
        $images = $leonardo->generateImages(
            $prompts['featured'],
            $prompts['middle'],
            $data['count'] ?? 3
        );

        return response()->json([
            'images' => $images,
        ]);
    }

    private function buildImagePrompts(array $data): array
    {
        $season = $this->seasonDescriptor();
        $currentPeriod = now()->format('F Y');
        $topic = $data['topic'] ?? 'European travel inspiration';
        $title = $data['title'] ?? null;
        $intro = trim((string) ($data['selected_intro'] ?? ''));
        $prompt = $data['prompt'] ?? null;

        $featuredPrompt = collect([
            $prompt,
            $title ? 'Editorial travel hero image for "' . $title . '"' : null,
            $topic ? 'Focus on European journey about ' . $topic : null,
            $intro ? 'Match the mood of: "' . Str::limit($intro, 100) . '"' : null,
            "Seasonal cues: {$season} {$currentPeriod} with weather-appropriate scenery, cinematic lighting, and human moments.",
            'Photo-real travel photography, wide hero framing, aspirational yet grounded.',
        ])->filter()->implode(' ');

        $introKeywords = $this->extractIntroKeywords($intro);

        $middlePrompt = collect([
            $introKeywords ? 'Detail story built around: ' . $introKeywords : null,
            $intro ? 'Pull sensory cues from intro: "' . Str::limit($intro, 120) . '"' : null,
            $topic ? 'Keep anchors to ' . $topic : null,
            "Seasonal cues: {$season} {$currentPeriod} highlighting textures, culinary details, and candid portraits.",
            'Editorial lifestyle shot, immersive close or three-quarter frame, grounded in reality.',
        ])->filter()->implode(' ');

        if (!$featuredPrompt) {
            $featuredPrompt = "Photo-real European travel inspiration during {$season} {$currentPeriod} about {$topic}.";
        }

        if (!$middlePrompt) {
            $middlePrompt = "Editorial detail moments capturing the spirit of {$topic} across {$season} {$currentPeriod}.";
        }

        return [
            'featured' => $featuredPrompt,
            'middle' => $middlePrompt,
        ];
    }

    private function extractIntroKeywords(?string $intro): string
    {
        if (!$intro) {
            return '';
        }

        $clean = strip_tags($intro);
        $words = str_word_count(Str::lower($clean), 1);

        if (empty($words)) {
            return '';
        }

        $stopWords = [
            'the', 'with', 'from', 'that', 'your', 'into', 'this', 'will', 'have', 'about',
            'their', 'they', 'them', 'then', 'when', 'what', 'where', 'which', 'like', 'make',
            'just', 'more', 'into', 'each', 'most', 'such',
        ];

        return collect($words)
            ->reject(fn ($word) => strlen($word) < 4 || in_array($word, $stopWords))
            ->unique()
            ->take(8)
            ->map(fn ($word) => Str::replace('_', ' ', Str::title($word)))
            ->implode(', ');
    }

    private function seasonDescriptor(): string
    {
        $month = (int) now()->month;

        return match (true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            default => 'autumn',
        };
    }
}
