<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $metrics = [
            'total' => Blog::count(),
            'drafts' => Blog::where('status', 'draft')->count(),
            'active' => Blog::where('status', 'active')->count(),
            'published' => Blog::where('status', 'published')->count(),
        ];

        return view('admin.dashboard', compact('metrics'));
    }
}
