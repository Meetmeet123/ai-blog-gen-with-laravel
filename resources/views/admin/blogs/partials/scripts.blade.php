@once
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
@endonce
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const builder = document.getElementById('blogBuilder');
        if (!builder) {
            return;
        }

        const MAX_CONTENT_LENGTH = 2500;
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const topicInput = document.getElementById('topicInput');
        const titleInput = document.getElementById('titleInput');
        const introSuggestions = document.getElementById('introSuggestions');
        const selectedIntro = document.getElementById('selectedIntro');
        const slugInput = document.getElementById('slugInput');
        const slugSuggestions = document.getElementById('slugSuggestions');
        const outlinePreview = document.getElementById('outlinePreview');
        const imageSuggestions = document.getElementById('imageSuggestions');
        const featuredInput = document.getElementById('featuredInput');
        const middleInput = document.getElementById('middleInput');
        const featuredPreview = document.getElementById('featuredPreview');
        const middlePreview = document.getElementById('middlePreview');
        const featuredPlaceholder = document.getElementById('featuredPreviewPlaceholder');
        const middlePlaceholder = document.getElementById('middlePreviewPlaceholder');
        const contentInput = document.getElementById('contentEditor');
        const contentCharCount = document.getElementById('contentCharCount');
        const form = document.getElementById('blogForm');

        const previewEls = {
            topic: document.getElementById('previewTopic'),
            title: document.getElementById('previewTitle'),
            slug: document.getElementById('previewSlug'),
            intro: document.getElementById('previewIntro'),
            content: document.getElementById('previewContent'),
            featured: document.getElementById('previewFeaturedImage'),
            middle: document.getElementById('previewMiddleImage'),
        };

        const placeholders = {
            topic: previewEls.topic?.textContent?.trim() || 'Blog topic',
            title: previewEls.title?.textContent?.trim() || 'Your standout headline appears here',
            intro: previewEls.intro?.textContent?.trim() || 'Select an intro to see it here.',
            featuredImg: builder.dataset.featuredPlaceholder,
            middleImg: builder.dataset.middlePlaceholder,
        };

        const defaultPreviewCopy = previewEls.content?.innerHTML || '<p>Your generated or edited article preview will render here.</p>';
        const slugBase = (builder.dataset.publicUrl || `${window.location.origin}/blog`).replace(/\/$/, '');

        const endpoints = {
            intros: builder.dataset.introsUrl,
            slug: builder.dataset.slugUrl,
            content: builder.dataset.contentUrl,
            images: builder.dataset.imagesUrl,
        };

        const buttons = {
            intros: document.getElementById('generateIntrosBtn'),
            moreIntros: document.getElementById('moreIntrosBtn'),
            slug: document.getElementById('generateSlugBtn'),
            content: document.getElementById('generateContentBtn'),
            images: document.getElementById('generateImagesBtn'),
        };

        let editorInstance = null;

        const escapeHtml = (value = '') => value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const plainTextFromHtml = (html = '') => {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return (tmp.textContent || tmp.innerText || '').trim();
        };

        const convertPlainToHtml = (text = '') => {
            return text
                .split(/\r?\n/)
                .map(line => line.trim())
                .filter(Boolean)
                .map(line => {
                    const heading = line.match(/^(#{1,6})\s+(.*)$/);
                    if (heading) {
                        const level = Math.min(heading[1].length + 2, 6);
                        return `<h${level}>${escapeHtml(heading[2])}</h${level}>`;
                    }
                    return `<p>${escapeHtml(line)}</p>`;
                })
                .join('');
        };

        const hasHtmlTags = (value = '') => /<\s*(p|h[1-6]|ul|ol|li|br|strong|em)/i.test(value);

        const formatContentForPreview = (value = '') => {
            if (!value?.trim()) {
                return '';
            }
            return hasHtmlTags(value) ? value : convertPlainToHtml(value);
        };

        const limitContent = (value = '') => {
            const plain = plainTextFromHtml(value);
            if (plain.length <= MAX_CONTENT_LENGTH) {
                return value;
            }
            const truncated = plain.slice(0, MAX_CONTENT_LENGTH).trim();
            return convertPlainToHtml(`${truncated}…`);
        };

        const updateCharDisplay = (value = '') => {
            const count = plainTextFromHtml(value).length;
            if (contentCharCount) {
                contentCharCount.textContent = `${count} / ${MAX_CONTENT_LENGTH}`;
                contentCharCount.classList.toggle('text-danger', count > MAX_CONTENT_LENGTH);
            }
            return count;
        };

        const updatePreviewContent = (value = '') => {
            const formatted = formatContentForPreview(value);
            if (previewEls.content) {
                previewEls.content.innerHTML = formatted || defaultPreviewCopy;
            }
        };

        const updateBasicPreviewText = () => {
            if (previewEls.topic) {
                previewEls.topic.textContent = topicInput?.value.trim() || placeholders.topic;
            }
            if (previewEls.title) {
                previewEls.title.textContent = titleInput?.value.trim() || placeholders.title;
            }
            if (previewEls.slug) {
                const slugValue = (slugInput?.value.trim() || 'your-slug').replace(/^\//, '');
                previewEls.slug.textContent = `${slugBase}/${slugValue}`;
            }
            if (previewEls.intro) {
                previewEls.intro.textContent = selectedIntro?.value.trim() || placeholders.intro;
            }
        };

        const updateImagePreview = (type) => {
            const input = type === 'featured' ? featuredInput : middleInput;
            const previewImg = type === 'featured' ? featuredPreview : middlePreview;
            const placeholderEl = type === 'featured' ? featuredPlaceholder : middlePlaceholder;
            const livePreviewImg = type === 'featured' ? previewEls.featured : previewEls.middle;
            const fallback = type === 'featured' ? placeholders.featuredImg : placeholders.middleImg;
            const url = (input?.value || '').trim();

            if (!input) {
                return;
            }

            if (url) {
                if (previewImg) {
                    previewImg.src = url;
                    previewImg.classList.remove('d-none');
                }
                placeholderEl?.classList.add('d-none');
                if (livePreviewImg) {
                    livePreviewImg.src = url;
                }
            } else {
                if (previewImg) {
                    previewImg.src = '';
                    previewImg.classList.add('d-none');
                }
                placeholderEl?.classList.remove('d-none');
                if (livePreviewImg) {
                    livePreviewImg.src = fallback;
                }
            }
        };

        const handleContentChange = () => {
            const html = editorInstance ? editorInstance.getData() : (contentInput?.value || '');
            updateCharDisplay(html);
            updatePreviewContent(html);
        };

        const hydrateInitialState = () => {
            updateBasicPreviewText();
            updateImagePreview('featured');
            updateImagePreview('middle');
            handleContentChange();
        };

        if (window.ClassicEditor && contentInput) {
            ClassicEditor
                .create(contentInput)
                .then(editor => {
                    editorInstance = editor;
                    handleContentChange();
                    editor.model.document.on('change:data', handleContentChange);
                })
                .catch(() => {
                    console.warn('CKEditor could not initialise. Falling back to textarea.');
                    contentInput?.addEventListener('input', handleContentChange);
                });
        } else {
            contentInput?.addEventListener('input', handleContentChange);
        }

        const post = async (url, payload = {}, button = null, loadingText = 'Working...') => {
            if (!url) return null;
            const originalText = button ? button.innerHTML : null;
            if (button) {
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    const message = await response.text();
                    throw new Error(message || 'Unable to reach AI service.');
                }

                return await response.json();
            } catch (error) {
                alert(error.message || 'Something went wrong while talking to the AI services.');
                throw error;
            } finally {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            }
        };

        const ensureTopic = () => {
            const topic = topicInput.value.trim();
            if (!topic) {
                alert('Please add a blog topic first.');
                return null;
            }
            return topic;
        };

        const renderIntros = (intros = []) => {
            introSuggestions.innerHTML = '';
            if (!intros.length) {
                const empty = document.createElement('p');
                empty.className = 'text-muted mb-0';
                empty.textContent = 'No suggestions yet.';
                introSuggestions.appendChild(empty);
                return;
            }

            intros.forEach((intro) => {
                const card = document.createElement('div');
                card.className = 'card border mb-2 intro-card';
                const body = document.createElement('div');
                body.className = 'card-body';

                const para = document.createElement('p');
                para.className = 'mb-3';
                para.textContent = intro;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-primary';
                btn.textContent = 'Use this intro';
                btn.addEventListener('click', () => selectIntro(intro, card));

                body.appendChild(para);
                body.appendChild(btn);
                card.appendChild(body);
                introSuggestions.appendChild(card);
            });
        };

        const selectIntro = (intro, card) => {
            selectedIntro.value = intro;
            document.querySelectorAll('.intro-card').forEach(el => el.classList.remove('border-primary'));
            if (card) {
                card.classList.add('border-primary');
            }
            updateBasicPreviewText();
        };

        const renderSlugOptions = (slugs = []) => {
            slugSuggestions.innerHTML = '';
            slugs.forEach((slug) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-light btn-sm me-2 mb-1';
                btn.textContent = slug;
                btn.addEventListener('click', () => {
                    slugInput.value = slug;
                    updateBasicPreviewText();
                });
                slugSuggestions.appendChild(btn);
            });
        };

        const renderOutline = (outline) => {
            outlinePreview.innerHTML = '';
            if (!outline) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'border rounded p-3 bg-light';

            const heading = document.createElement('p');
            heading.className = 'fw-semibold mb-2';
            heading.textContent = outline.description || 'Outline';

            const list = document.createElement('ul');
            list.className = 'mb-0';
            (outline.sections || []).forEach(section => {
                const li = document.createElement('li');
                li.textContent = section;
                list.appendChild(li);
            });

            wrapper.appendChild(heading);
            wrapper.appendChild(list);
            outlinePreview.appendChild(wrapper);
        };

        const renderImages = (images = []) => {
            if (!imageSuggestions) {
                return;
            }

            imageSuggestions.innerHTML = '';
            const sets = Array.isArray(images) ? images : Object.values(images || {});

            if (!sets.length) {
                const empty = document.createElement('p');
                empty.className = 'text-muted mb-0';
                empty.textContent = 'No image suggestions yet.';
                imageSuggestions.appendChild(empty);
                return;
            }

            sets.forEach((set, index) => {
                const col = document.createElement('div');
                col.className = 'col-sm-6 col-xl-4';

                const card = document.createElement('div');
                card.className = 'card h-100 shadow-sm';

                const header = document.createElement('div');
                header.className = 'card-header fw-semibold small text-uppercase';
                header.textContent = `Concept ${index + 1}`;
                card.appendChild(header);

                const body = document.createElement('div');
                body.className = 'card-body d-flex flex-column gap-3';

                ['featured', 'middle'].forEach((type) => {
                    const url = set?.[type];
                    if (!url) {
                        return;
                    }

                    const section = document.createElement('div');

                    const label = document.createElement('p');
                    label.className = 'text-muted text-uppercase small mb-1';
                    label.textContent = type === 'featured' ? 'Featured visual' : 'Middle visual';

                    const ratio = document.createElement('div');
                    ratio.className = 'ratio ratio-16x9 rounded bg-light overflow-hidden mb-2';

                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = `${type} suggestion`;
                    img.className = 'w-100 h-100 object-fit-cover';
                    ratio.appendChild(img);

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `btn btn-outline-${type === 'featured' ? 'primary' : 'secondary'} btn-sm`;
                    btn.textContent = `Use as ${type}`;
                    btn.addEventListener('click', () => {
                        if (type === 'featured') {
                            featuredInput.value = url;
                            updateImagePreview('featured');
                        } else {
                            middleInput.value = url;
                            updateImagePreview('middle');
                        }
                    });

                    section.appendChild(label);
                    section.appendChild(ratio);
                    section.appendChild(btn);
                    body.appendChild(section);
                });

                card.appendChild(body);
                col.appendChild(card);
                imageSuggestions.appendChild(col);
            });
        };

        const attachChangeListeners = () => {
            featuredInput?.addEventListener('change', () => updateImagePreview('featured'));
            middleInput?.addEventListener('change', () => updateImagePreview('middle'));
            topicInput?.addEventListener('input', updateBasicPreviewText);
            titleInput?.addEventListener('input', updateBasicPreviewText);
            slugInput?.addEventListener('input', updateBasicPreviewText);
            selectedIntro?.addEventListener('input', updateBasicPreviewText);
        };

        const requestIntros = (button) => {
            const topic = ensureTopic();
            if (!topic) return;
            post(endpoints.intros, {topic}, button, 'Generating')
                .then((data) => {
                    renderIntros(data?.intros || []);
                })
                .catch(() => {});
        };

        const applyGeneratedContent = (payload) => {
            if (!payload) {
                return;
            }
            const limited = limitContent(payload);
            if (editorInstance) {
                editorInstance.setData(limited);
            } else if (contentInput) {
                contentInput.value = limited;
            }
            handleContentChange();
        };

        buttons.intros?.addEventListener('click', () => requestIntros(buttons.intros));
        buttons.moreIntros?.addEventListener('click', () => requestIntros(buttons.moreIntros));

        buttons.slug?.addEventListener('click', () => {
            const topic = ensureTopic();
            if (!topic) return;
            post(endpoints.slug, {
                topic,
                selected_intro: selectedIntro.value,
            }, buttons.slug, 'Cooking')
                .then((data) => {
                    if (data?.default) {
                        slugInput.value = data.default;
                        updateBasicPreviewText();
                    }
                    renderSlugOptions(data?.slugs || []);
                })
                .catch(() => {});
        });

        buttons.content?.addEventListener('click', () => {
            const topic = ensureTopic();
            const intro = selectedIntro.value.trim();
            if (!topic || !intro) {
                alert('Select an intro first so the copy matches the idea.');
                return;
            }
            post(endpoints.content, {
                topic,
                selected_intro: intro,
            }, buttons.content, 'Drafting')
                .then((data) => {
                    renderOutline(data?.outline);
                    if (data?.content) {
                        applyGeneratedContent(data.content);
                    }
                })
                .catch(() => {});
        });

        buttons.images?.addEventListener('click', () => {
            const intro = selectedIntro.value.trim();
            const topic = ensureTopic();
            if (!topic) return;
            const payload = {
                topic,
                title: titleInput?.value.trim() || '',
                selected_intro: intro,
                prompt: intro || topic,
                count: 3,
            };
            post(endpoints.images, payload, buttons.images, 'Rendering')
                .then((data) => {
                    renderImages(data?.images || []);
                })
                .catch(() => {});
        });

        attachChangeListeners();
        hydrateInitialState();

        form?.addEventListener('submit', () => {
            if (editorInstance) {
                document.getElementById('contentEditor').value = editorInstance.getData();
            }
        });
    });
</script>
