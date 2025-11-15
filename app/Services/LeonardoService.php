<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LeonardoService
{
    private ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    public function enabled(): bool
    {
        return filled($this->apiKey ?? config('services.leonardo.key'));
    }

    public function generateImages(string $featuredPrompt, string $middlePrompt, int $pairCount = 3): array
    {
        $key = $this->apiKey ?? config('services.leonardo.key');
        if (!$key) return [];

        $pairCount = max(1, $pairCount);

        $featured = $this->requestImages($key, $featuredPrompt, $pairCount);
        $middle   = $this->requestImages($key, $middlePrompt, $pairCount);

        $pairs = [];
        for ($i = 0; $i < $pairCount; $i++) {
            $pairs[] = [
                'featured' => $featured[$i] ?? "",
                'middle'   => $middle[$i] ?? "",
            ];
        }

        return $pairs;
    }

    private function requestImages(string $key, string $prompt, int $count): array
    {
        $payload = [
            "prompt" => $prompt,
            "modelId" => "e316348f-7773-490e-adcd-46757c738eb7",
            "num_images" => $count,
            "width"  => 1024,
            "height" => 768,
        ];

        // Step 1: Request generation job
        $response = Http::timeout(300)
            ->withHeaders([
                "Authorization" => "Bearer {$key}",
                "accept" => "application/json",
            ])
        ->post("https://cloud.leonardo.ai/api/rest/v1/generations", $payload);

        if ($response->failed()) return [];

        $generationId = data_get($response->json(), "sdGenerationJob.generationId");

        if (!$generationId) {
            return [];
        }

        // Step 2: Poll until status COMPLETE
        $result = $this->waitForGenerationCompletion($key, $generationId);

        if (empty($result)) return [];

        // Step 3: extract images
        $images = data_get($result, "generations_by_pk.generated_images", []);

        return collect($images)
            ->map(fn($img) => $img["url"] ?? null)
            ->filter()
            ->values()
        ->all();
    }

    private function waitForGenerationCompletion(string $key, string $generationId): array
    {
        for ($i = 0; $i < 15; $i++) {

            $response = Http::timeout(300)
                ->withHeaders([
                    "Authorization" => "Bearer {$key}",
                    "accept" => "application/json",
                ])
                ->get("https://cloud.leonardo.ai/api/rest/v1/generations/{$generationId}");

            if ($response->failed()) return [];

            $data = $response->json();

            $status = Str::upper((string) data_get($data, "generations_by_pk.status"));

            if ($status === "COMPLETE") {
                return $data;
            }

            if (in_array($status, ["FAILED", "ERROR", "CANCELED"])) {
                return [];
            }

            sleep(2);
        }

        return [];
    }
}
