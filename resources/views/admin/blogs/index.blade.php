@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Generated Blogs</h1>
            <p class="text-muted mb-0">Filter by status to keep drafts, published, and deleted posts under control.</p>
        </div>
        <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">+ New Blog</a>
    </div>

    <form method="GET" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected($activeStatus === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Title, topic or slug"
                       value="{{ $activeSearch }}">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label d-none d-md-block">&nbsp;</label>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary flex-grow-1">Apply</button>
                    @if($activeStatus || $activeSearch)
                        <a href="{{ route('admin.blogs.index') }}" class="btn btn-outline-secondary" title="Clear filters">Reset</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width: 90px;">Sr. No.</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @php($start = ($blogs->currentPage() - 1) * $blogs->perPage())
                @forelse($blogs as $blog)
                    <tr>
                        <td>{{ $start + $loop->iteration }}</td>
                        <td>
                            <strong>{{ $blog->title }}</strong>
                            <div class="text-muted small">{{ $blog->topic }}</div>
                        </td>
                        <td>{{ $blog->slug }}</td>
                        <td>
                            <span class="badge bg-light text-dark text-uppercase">{{ $blog->status }}</span>
                        </td>
                        <td>{{ $blog->updated_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.blogs.preview', $blog) }}" class="btn btn-link btn-sm" target="_blank">Preview</a>
                            @if($blog->status === 'published')
                                <a href="{{ route('blogs.show', $blog->slug) }}" class="btn btn-link btn-sm" target="_blank">View live</a>
                            @endif
                            <a href="{{ route('admin.blogs.edit', $blog) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            <form action="{{ route('admin.blogs.destroy', $blog) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Move to deleted?')" type="submit">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No blog posts yet. Generate your first story!</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $blogs->withQueryString()->links() }}
        </div>
    </div>
@endsection
