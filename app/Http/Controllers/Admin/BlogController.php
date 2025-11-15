<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Support\StorageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');

        $blogs = Blog::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query, $term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('topic', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                });
            })
            ->latest()
            ->paginate(10);

        return view('admin.blogs.index', [
            'blogs' => $blogs,
            'statuses' => Blog::STATUSES,
            'activeStatus' => $status,
            'activeSearch' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.blogs.create', [
            'blog' => new Blog([
                'status' => 'draft',
            ]),
            'statuses' => Blog::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $blog = Blog::create($data);
        $this->syncBlogImages($blog, $data);

        return redirect()
            ->route('admin.blogs.edit', $blog)
            ->with('success', 'Blog saved. You can continue refining it below.');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.edit', [
            'blog' => $blog,
            'statuses' => Blog::STATUSES,
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validatedData($request, $blog->id);
        $originalImages = [
            'featured_img_path' => $blog->getOriginal('featured_img_path'),
            'middle_img_path' => $blog->getOriginal('middle_img_path'),
        ];
        $blog->update($data);
        $this->syncBlogImages($blog, $data, $originalImages);

        return redirect()
            ->route('admin.blogs.edit', $blog)
            ->with('success', 'Blog updated.');
    }

    public function destroy(Blog $blog)
    {
        $blog->update(['status' => 'deleted']);

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', 'Blog moved to deleted status.');
    }

    public function preview(Blog $blog)
    {
        abort_if($blog->status === 'deleted', 404);

        return view('blogs.show', [
            'blog' => $blog,
            'isPreview' => true,
        ]);
    }

    private function validatedData(Request $request, ?int $blogId = null): array
    {
        $statusRule = 'in:' . implode(',', Blog::STATUSES);

        $payload = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'selected_intro' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'featured_img_path' => ['nullable', 'string', 'max:2048'],
            'middle_img_path' => ['nullable', 'string', 'max:2048'],
            'status' => ['required', $statusRule],
            'ai_metadata' => ['nullable', 'array'],
        ]);

        $payload['slug'] = Str::slug($payload['slug']);

        if (isset($payload['ai_metadata'])) {
            $payload['ai_metadata'] = array_filter($payload['ai_metadata']);
        }

        foreach (['featured_img_path', 'middle_img_path'] as $field) {
            if (array_key_exists($field, $payload) && blank($payload[$field])) {
                $payload[$field] = null;
            }
        }

        return $payload;
    }

    /**
     * Persist any remote blog images into local storage/blogs/{id}.
     */
    private function syncBlogImages(Blog $blog, array $payload, array $original = []): void
    {
        if (!$blog->id) {
            return;
        }

        $fields = [
            'featured_img_path' => 'featured',
            'middle_img_path' => 'middle',
        ];

        $updates = [];

        foreach ($fields as $field => $prefix) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = $payload[$field];
            $previous = array_key_exists($field, $original)
                ? $original[$field]
                : $blog->getOriginal($field);

            if (!$value) {
                if ($previous) {
                    $this->deleteLocalImage($previous);
                }
                continue;
            }

            $normalized = StorageUrl::normalize($value, 'blogs/');

            if ($previous && $value === $previous && $normalized) {
                $canonical = StorageUrl::publicUrl($normalized);

                if ($canonical !== $value) {
                    $updates[$field] = $canonical;
                }

                continue;
            }

            if ($normalized) {
                $updates[$field] = StorageUrl::publicUrl($normalized);
                continue;
            }

            $stored = $this->storeImageForBlog($blog, $value, $prefix);

            if ($stored) {
                if ($previous) {
                    $this->deleteLocalImage($previous);
                }
                $updates[$field] = $stored;
            }
        }

        if ($updates) {
            $blog->forceFill($updates)->save();
        }
    }

    private function storeImageForBlog(Blog $blog, string $source, string $prefix): ?string
    {
        if (!$blog->id) {
            return null;
        }

        $download = $this->downloadImageContents($source);

        if (!$download) {
            return null;
        }

        $extension = $download['extension'] ?: 'jpg';
        $filename = sprintf(
            '%s-%s-%s.%s',
            $prefix,
            now()->format('YmdHis'),
            Str::lower(Str::random(6)),
            $extension
        );
        $path = 'blogs/' . $blog->id . '/' . $filename;

        Storage::disk('public')->put($path, $download['contents']);

        return StorageUrl::publicUrl($path);
    }

    private function downloadImageContents(string $source): ?array
    {
        if (Str::startsWith($source, 'data:image')) {
            [$meta, $data] = explode(',', $source, 2);
            if (!isset($data)) {
                return null;
            }

            $contents = base64_decode($data, true);

            if ($contents === false) {
                return null;
            }

            $extension = 'png';

            if (preg_match('/data:image\/([\w\.\-]+);base64/i', $meta, $matches)) {
                $extension = strtolower($matches[1]);
            }

            return [
                'contents' => $contents,
                'extension' => $extension,
            ];
        }

        if (!filter_var($source, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            $response = Http::timeout(30)->get($source);
        } catch (Throwable $e) {
            report($e);
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $mime = $response->header('Content-Type');
        $extension = $this->extensionFromMime($mime)
            ?? pathinfo(parse_url($source, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)
            ?: 'jpg';

        return [
            'contents' => $response->body(),
            'extension' => strtolower($extension),
        ];
    }

    private function extensionFromMime(?string $mime): ?string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
    }

    private function deleteLocalImage(?string $path): void
    {
        $diskPath = StorageUrl::normalize($path, 'blogs/');

        if ($diskPath) {
            Storage::disk('public')->delete($diskPath);
        }
    }
}
