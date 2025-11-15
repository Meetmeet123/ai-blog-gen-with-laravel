@once
    @push('styles')
        <style>
            #blogBuilder .card {
                border-radius: 1rem;
            }
            #blogBuilder .live-preview-template {
                font-family: 'Montserrat', sans-serif;
                background-color: #fcfbf9;
                color: #333;
            }
            #blogBuilder .preview-pill {
                display: inline-flex;
                border: 1px solid #ccc;
                border-radius: 999px;
                padding: 0.2rem 0.8rem;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            #blogBuilder .preview-image {
                border-radius: 0.75rem;
                overflow: hidden;
                background: #f3f3f0;
            }
            #blogBuilder .preview-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            #blogBuilder .preview-content p {
                margin-bottom: 0.75rem;
                line-height: 1.6;
            }
            #blogBuilder .preview-content h3,
            #blogBuilder .preview-content h4,
            #blogBuilder .preview-content h5 {
                font-family: 'Cormorant Garamond', serif;
                margin-top: 1.25rem;
            }
            #blogBuilder #livePreviewCard {
                top: 90px;
            }
            #blogBuilder .image-uploader .ratio {
                height: 180px;
            }
            #blogBuilder #generateImagesBtn {
                width: 100%;
            }
            @media (min-width: 768px) {
                #blogBuilder #generateImagesBtn {
                    width: auto;
                }
            }
            @media (max-width: 991.98px) {
                #blogBuilder #livePreviewCard {
                    position: static !important;
                }
            }
        </style>
    @endpush
@endonce

<div id="blogBuilder"
     data-intros-url="{{ route('admin.ai.intros') }}"
     data-slug-url="{{ route('admin.ai.slug') }}"
     data-content-url="{{ route('admin.ai.content') }}"
     data-images-url="{{ route('admin.ai.images') }}"
     data-public-url="{{ url('blog') }}"
     data-featured-placeholder="https://placehold.co/640x360?text=Featured+visual"
     data-middle-placeholder="https://placehold.co/640x360?text=Body+visual">
    <div class="row g-4 align-items-start">
        <div class="col-lg-7 col-xl-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Blog topic / title seed</label>
                            <input type="text" class="form-control" id="topicInput" name="topic"
                                   value="{{ old('topic', $blog->topic) }}" required>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button type="button" class="btn btn-outline-primary mb-2 w-100" id="generateIntrosBtn">
                                Generate intros (x3)
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100" id="moreIntrosBtn">
                                Different intro set
                            </button>
                        </div>
                    </div>
                    <div class="intro-suggestions mt-3" id="introSuggestions"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Final blog title</label>
                            <input type="text" name="title" id="titleInput" class="form-control"
                                   value="{{ old('title', $blog->title) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SEO slug</label>
                            <div class="input-group">
                                <input type="text" id="slugInput" name="slug" class="form-control"
                                       value="{{ old('slug', $blog->slug) }}" required>
                                <button class="btn btn-outline-primary" type="button" id="generateSlugBtn">
                                    Suggest slug
                                </button>
                            </div>
                            <div class="small text-muted mt-1" id="slugSuggestions"></div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold">Selected intro</label>
                        <textarea name="selected_intro" id="selectedIntro" class="form-control" rows="3">{{ old('selected_intro', $blog->selected_intro) }}</textarea>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" id="generateContentBtn" class="btn btn-outline-success">
                                Build outline & content
                            </button>
                        </div>
                        <div class="mt-3" id="outlinePreview"></div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                <div>
                                    <label class="form-label fw-semibold mb-0">Blog content</label>
                                    <p class="small text-muted mb-0">Max 2,500 characters. Live preview updates automatically.</p>
                                </div>
                                <span class="badge bg-light text-dark" id="contentCharCount">0 / 2500</span>
                            </div>
                            <textarea name="content" id="contentEditor" rows="10" class="form-control">{{ old('content', $blog->content) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected(old('status', $blog->status) === $status)>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="small text-muted mt-2">Draft & active stay internal. Published entries surface on the public blog. Deleted hides it everywhere.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 image-uploader">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                        <div>
                            <label class="form-label fw-semibold mb-0">Leonardo imagery</label>
                            <p class="small text-muted mb-0">Generate new concepts or paste custom URLs. Suggestions appear below the button.</p>
                        </div>
                        <button type="button" id="generateImagesBtn" class="btn btn-outline-dark w-100 w-md-auto">
                            Generate Leonardo images
                        </button>
                    </div>
                    <div class="row g-3 mb-4" id="imageSuggestions"></div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Featured image</label>
                            <div class="ratio ratio-16x9 rounded bg-light mb-2 d-flex align-items-center justify-content-center overflow-hidden">
                                @if($blog->featured_img_path)
                                    <img src="{{ $blog->featured_img_path }}" class="w-100 h-100 object-fit-cover" id="featuredPreview" alt="Featured image">
                                @else
                                    <span class="text-muted" id="featuredPreviewPlaceholder">No image yet</span>
                                    <img src="" class="d-none w-100 h-100 object-fit-cover" id="featuredPreview" alt="">
                                @endif
                            </div>
                            <input type="text" class="form-control" name="featured_img_path" id="featuredInput"
                                   value="{{ old('featured_img_path', $blog->featured_img_path) }}" placeholder="https://">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Middle body image</label>
                            <div class="ratio ratio-16x9 rounded bg-light mb-2 d-flex align-items-center justify-content-center overflow-hidden">
                                @if($blog->middle_img_path)
                                    <img src="{{ $blog->middle_img_path }}" class="w-100 h-100 object-fit-cover" id="middlePreview" alt="Middle image">
                                @else
                                    <span class="text-muted" id="middlePreviewPlaceholder">No image yet</span>
                                    <img src="" class="d-none w-100 h-100 object-fit-cover" id="middlePreview" alt="">
                                @endif
                            </div>
                            <input type="text" class="form-control" name="middle_img_path" id="middleInput"
                                   value="{{ old('middle_img_path', $blog->middle_img_path) }}" placeholder="https://">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-xl-4">
            <div class="card shadow-sm border-0 sticky-lg-top" id="livePreviewCard">
                <div class="card-header bg-white border-0 pb-0">
                    <div>
                        <p class="text-uppercase small text-muted mb-1">Live preview</p>
                        <h6 class="mb-0">See how your blog will look as you type.</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="live-preview-template">
                        <div class="preview-pill mb-2" id="previewTopic">{{ old('topic', $blog->topic) ?: 'Blog topic' }}</div>
                        <h3 class="fw-bold" id="previewTitle">{{ old('title', $blog->title) ?: 'Your standout headline appears here' }}</h3>
                        <p class="text-muted small" id="previewSlug">{{ url('blog/' . (old('slug', $blog->slug) ?: 'your-slug')) }}</p>
                        <p class="fst-italic" id="previewIntro">{{ old('selected_intro', $blog->selected_intro) ?: 'Select an intro to see it here.' }}</p>
                        <div class="preview-image ratio ratio-16x9 mb-3">
                            <img src="{{ old('featured_img_path', $blog->featured_img_path) ?: 'https://placehold.co/640x360?text=Featured+visual' }}" id="previewFeaturedImage" alt="Featured visual">
                        </div>
                        <div class="preview-content" id="previewContent">
                            {!! $blog->formatted_content ?: '<p>Your generated or edited article preview will render here. Use the outline builder to craft something polished.</p>' !!}
                        </div>
                        <div class="preview-image ratio ratio-16x9 mt-3">
                            <img src="{{ old('middle_img_path', $blog->middle_img_path) ?: 'https://placehold.co/640x360?text=Body+visual' }}" id="previewMiddleImage" alt="Inline visual">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
        <div class="text-muted small">
            Need fresh inspiration? Use the buttons above to regenerate intros, copywriting, and artwork until it shines.
        </div>
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save Blog' }}</button>
    </div>
</div>
