<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAiService
{
    private ?string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, string $model = 'gpt-4o-mini')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function enabled(): bool
    {
        return filled($this->apiKey ?? config('services.openai.key'));
    }

    public function generateIntros(string $topic, int $count = 3): array
    {
        $prompt = "You are a creative blog strategist. Suggest {$count} punchy blog intros for the topic \"{$topic}\". "
            . 'Return the suggestions as a plain numbered list without any extra commentary.';

        $content = $this->request($prompt);
        $intros = $this->extractList($content, $count);

        if (empty($intros)) {
            $intros = $this->fallbackIntros($topic, $count);
        }

        return $intros;
    }

    public function generateSlugSuggestions(string $topic, string $selectedIntro = '', int $count = 3): array
    {
        $prompt = "Generate {$count} SEO friendly slugs for a blog about {$topic}. "
            . 'Base the tone on the following intro if it exists: ' . $selectedIntro;

        $content = $this->request($prompt);
        $slugs = collect($this->extractList($content, $count))
            ->map(fn ($slug) => Str::slug($slug))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($slugs)) {
            $slugs = [
                Str::slug($topic),
                Str::slug($topic . ' insights'),
                Str::slug($topic . ' guide'),
            ];
        }

        return array_slice($slugs, 0, $count);
    }

    public function generateContentOutline(string $topic, string $intro = ''): array
    {
        $prompt = "Create a concise blog outline and short description for \"{$topic}\". "
            . 'Use the intro below if supplied to keep tone consistent. Provide 3-4 section titles and a short summary. '
            . "Intro: {$intro}";

        $content = $this->request($prompt);

        if (!$content) {
            return [
                'description' => $intro ?: "Fresh ideas around {$topic}.",
                'sections' => [
                    "Why {$topic} matters",
                    "Key takeaways",
                    "How to take action",
                ],
            ];
        }

        return [
            'description' => Str::of($content)->before("\n")->trim() ?: $intro,
            'sections' => $this->extractList($content, 4) ?: [$content],
        ];
    }

    public function generateBodyContent(string $topic, string $intro): string
    {
        $season = $this->seasonDescriptor();
        $currentPeriod = now()->format('F Y');

        $prompt = "You are a seasoned professional blogger. Write a polished article about \"{$topic}\" that keeps the intro below verbatim, then expands into 3-4 markdown-ready sections with h3-style headings, authoritative yet warm paragraphs, and actionable insights. "
            . 'Close with a short reflective paragraph and a bolded "Key Takeaways" list of 3 bullets. '
            . "Intro: {$intro}. "
            . "Make the guidance feel timely by referencing {$season} conditions, weather, or cultural moments happening around {$currentPeriod} across Europe so the copy feels current.";

        $content = $this->request($prompt);

        if (!$content) {
            $fallback = <<<MARKDOWN
{$intro}

### Why {$topic} matters
Seasoned bloggers highlight the stakes before diving into the how-to. Explain what is changing, why it matters for decision makers, and set expectations for the rest of the article.

### Practical moves to lean on
Offer two or three concrete actions the reader can take right away. Keep the tone confident, professional, and rooted in real-world experience.

### Keep the momentum going
Wrap up with a motivating paragraph that invites the reader to apply the insight, subscribe, or continue the conversation.

**Key Takeaways**
- Anchor your narrative in the intro so the story stays cohesive.
- Translate observations about {$topic} into a clear action item.
- Close with energy so readers know what to do next.
MARKDOWN;

            $content = trim($fallback);
        } else {
            $content = trim($content);
        }

        if (Str::length($content) > 2500) {
            $content = Str::limit($content, 2500, '…');
        }

        return $content;
    }

    private function request(string $prompt): ?string
    {
        $key = $this->apiKey ?? config('services.openai.key');

        if (!$key) {
            return null;
        }

        $response = Http::timeout(30)
            ->withToken($key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', $this->model),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful marketing copywriter.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.8,
            ]);

        if ($response->failed()) {
            return null;
        }

        return data_get($response->json(), 'choices.0.message.content');
    }

    private function extractList(?string $content, int $max): array
    {
        if (!$content) {
            return [];
        }

        $lines = collect(preg_split('/\r\n|\r|\n/', $content))
            ->map(fn ($line) => trim(preg_replace('/^\d+\.\s*/', '', $line)))
            ->filter()
            ->take($max)
            ->values()
            ->all();

        return $lines;
    }

    private function fallbackIntros(string $topic, int $count): array
    {
        $templates = [
            'Idea %d: Frame %s as the smart seasonal move and hint at the takeaway readers will get.',
            'Idea %d: Paint a sensory snapshot of %s so the audience feels like they just arrived.',
            'Idea %d: Surface a surprising tension around %s and promise to unpack it in the post.',
            'Idea %d: Share a quick stat or win tied to %s as proof the topic matters right now.',
            'Idea %d: Invite readers into a vivid moment from %s before zooming out to your thesis.',
            'Idea %d: Highlight how %s is shifting this month and tease the actionable guidance ahead.',
        ];

        return collect(range(1, $count))
            ->map(function ($idx) use ($templates, $topic) {
                $template = $templates[($idx - 1) % count($templates)];

                return sprintf($template, $idx, $topic);
            })
            ->all();
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
