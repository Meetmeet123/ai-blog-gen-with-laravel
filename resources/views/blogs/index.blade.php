@extends('layouts.app')

@section('content')
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Fresh AI-assisted stories</h1>
        <p class="text-muted">Curated by your team in the admin studio, published for everyone.</p>
    </div>

    <div class="row g-4">
        @forelse($blogs as $blog)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    @if($blog->featured_img_path)
                        <img src="{{ $blog->featured_img_path }}" class="card-img-top" alt="{{ $blog->title }}">
                    @endif
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-dark-subtle mb-2 text-uppercase text-muted">{{ $blog->topic }}</span>
                        <h2 class="h5">{{ $blog->title }}</h2>
                        <p class="text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit(strip_tags($blog->selected_intro ?? $blog->content), 120) }}</p>
                        <a href="{{ route('blogs.show', $blog->slug) }}" class="btn btn-outline-primary mt-3">Read blog</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    No published blogs yet. Check back soon.
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $blogs->links() }}
    </div>
@endsection
