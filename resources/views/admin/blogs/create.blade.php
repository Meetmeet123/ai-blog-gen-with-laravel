@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Generate a new blog</h1>
            <p class="text-muted mb-0">Start with a topic, let AI suggest intros, then curate content, slug, and imagery.</p>
        </div>
        <a href="{{ route('admin.blogs.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <form method="POST" action="{{ route('admin.blogs.store') }}" id="blogForm">
        @csrf
        @include('admin.blogs.partials.form', ['submitLabel' => 'Create blog'])
    </form>
@endsection

@push('scripts')
    @include('admin.blogs.partials.scripts')
@endpush
