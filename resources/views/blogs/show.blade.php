@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .blog-template {
            font-family: 'Montserrat', sans-serif;
            background-color: #fcfbf9;
            color: #333;
            padding: 2rem;
        }
        .blog-template .blog-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .blog-template img {
            max-width: 100%;
            border-radius: 8px;
            display: block;
        }
        .blog-template header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 2rem;
        }
        .blog-template header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 3rem;
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        .blog-template .script-header {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            font-style: italic;
            font-size: 1.5rem;
        }
        .blog-template .pill {
            display: inline-flex;
            border: 1px solid #ccc;
            border-radius: 50px;
            padding: 0.4rem 1.2rem;
            font-size: 0.8rem;
            background-color: #fff;
            text-transform: uppercase;
        }
        .blog-template .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            align-items: center;
            padding: 2rem 0;
            border-bottom: 1px solid #eee;
        }
        .blog-template .text-content h3 {
            font-family: 'Cormorant Garamond', serif;
            letter-spacing: 3px;
            font-size: 1.1rem;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        .blog-template .text-content p {
            color: #555;
        }
        .blog-template .rich-body h2,
        .blog-template .rich-body h3 {
            font-family: 'Cormorant Garamond', serif;
            margin-top: 1.5rem;
        }
        .blog-template .rich-body p {
            margin-bottom: 1rem;
            line-height: 1.8;
        }
        .blog-template footer {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            text-align: center;
            padding: 2rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-top: 2rem;
        }
        .blog-template footer a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .blog-template header h1 {
                font-size: 2.2rem;
            }
            .blog-template {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    @php($isPreview = $isPreview ?? false)
    <div class="blog-template">
        <div class="blog-container">
            @if($isPreview)
                <div class="alert alert-warning mb-4">
                    You're viewing a private preview. Publish this post to make it accessible on the public blog.
                </div>
            @endif
            <header>
                <div class="pill">{{ $blog->topic }}</div>
                <h1>{{ $blog->title }}</h1>
                <div class="script-header">
                    <span>{{ url('blog/' . $blog->slug) }}</span>
                </div>
                @if($blog->selected_intro)
                    <p class="mt-3 text-muted">{!! nl2br(e($blog->selected_intro)) !!}</p>
                @endif
            </header>

            <div class="row">
                @if($blog->featured_img_path)
                    <div class="image-content">
                        <img src="{{ $blog->featured_img_path }}" alt="{{ $blog->title }} featured image">
                    </div>
                @endif
                <div class="text-content">
                    <h3>Why this story matters</h3>
                    <p>{{ $blog->selected_intro ?? 'Handcrafted using our AI blogging studio.' }}</p>
                </div>
            </div>

            <div class="row">
                <div class="text-content">
                    <h3>Inside the article</h3>
                    <div class="rich-body">
                        {!! $blog->formatted_content !!}
                    </div>
                </div>
                @if($blog->middle_img_path)
                    <div class="image-content">
                        <img src="{{ $blog->middle_img_path }}" alt="Detail image">
                    </div>
                @endif
            </div>

            <footer>
                <a href="{{ route('blogs.public') }}">← All blogs</a>
                @if($blog->status === 'published')
                    <a href="{{ route('blogs.show', $blog->slug) }}">Share this link</a>
                @elseif($isPreview)
                    <span class="text-muted">Share link available once published</span>
                @endif
                <a href="{{ url()->previous() }}">Back</a>
            </footer>
        </div>
    </div>
@endsection
