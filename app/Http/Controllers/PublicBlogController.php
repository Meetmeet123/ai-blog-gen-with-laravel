<?php

namespace App\Http\Controllers;

use App\Models\Blog;

class PublicBlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::published()->latest()->paginate(6);

        return view('blogs.index', compact('blogs'));
    }

    public function show(string $slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('blogs.show', [
            'blog' => $blog,
            'isPreview' => false,
        ]);
    }
}
