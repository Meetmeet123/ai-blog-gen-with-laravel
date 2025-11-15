@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit blog</h1>
            <p class="text-muted mb-0">Keep iterating. Regenerate images or copy at any time.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.blogs.preview', $blog) }}" class="btn btn-outline-secondary" target="_blank">Preview</a>
            @if($blog->status === 'published')
                <a href="{{ route('blogs.show', $blog->slug) }}" class="btn btn-outline-primary" target="_blank">View public page</a>
            @else
                <span class="text-muted small">Publish the blog to get a live link.</span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.blogs.update', $blog) }}" id="blogForm">
        @csrf
        @method('PUT')
        @include('admin.blogs.partials.form', ['submitLabel' => 'Update blog'])
    </form>
@endsection

@push('scripts')
    @include('admin.blogs.partials.scripts')
@endpush
