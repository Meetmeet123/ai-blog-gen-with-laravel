@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Dashboard</h1>
            <p class="text-muted mb-0">Track ideas and launch AI assisted posts.</p>
        </div>
        <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">
            + New AI Blog
        </a>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Total</p>
                    <p class="fs-2 fw-bold mb-0">{{ $metrics['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Drafts</p>
                    <p class="fs-2 fw-bold mb-0">{{ $metrics['drafts'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Active</p>
                    <p class="fs-2 fw-bold mb-0">{{ $metrics['active'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Published</p>
                    <p class="fs-2 fw-bold mb-0">{{ $metrics['published'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
