// ===== SIDEBAR HOVER FUNCTIONALITY =====
// Sidebar starts collapsed and expands on hover (handled by CSS)
// No JavaScript needed for hover expand/collapse

// All handlers run after DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // ===== THEME TOGGLE =====
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const savedTheme = localStorage.getItem('theme');
    
    // Apply saved theme
    if (savedTheme === 'light') {
        body.classList.add('light-mode');
    }
    
    // Add click handler
    if (themeToggle) {
        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('light-mode');
            const newTheme = body.classList.contains('light-mode') ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
        });
    }

    // Auto-resize behavior for the top textarea (.create-post-input)
    function autoResizeTextarea(el) {
        if (!el) return;
        // Reset height to compute the correct scrollHeight
        el.style.height = 'auto';
        // Add a small extra so the caret isn't right at the edge
        el.style.height = (el.scrollHeight + 2) + 'px';
    }

    document.querySelectorAll('.create-post-input').forEach(textarea => {
        // Initialize height based on current content
        autoResizeTextarea(textarea);
        // Listen for input changes
        textarea.addEventListener('input', function () {
            autoResizeTextarea(this);
        });
        // Optional: on window resize recompute (keeps width changes in mind)
        window.addEventListener('resize', function () { autoResizeTextarea(textarea); });
    });

    // Word count validation for post submissions
    const inlinePostForm = document.getElementById('inline-post-form');
    const contentTextarea = inlinePostForm ? inlinePostForm.querySelector('textarea[name="content"]') : null;
    const wordCountDisplay = document.getElementById('wordCount');
    const MAX_WORDS = 500;

    if (contentTextarea && inlinePostForm) {
        inlinePostForm.addEventListener('submit', function (e) {
            const content = contentTextarea.value.trim();
            const wordCount = content.split(/\s+/).filter(word => word.length > 0).length;
            
            if (wordCount > MAX_WORDS) {
                e.preventDefault();
                alert(`Your post exceeds the 500-word limit. Current word count: ${wordCount}`);
            }
        });

        // Prevent typing beyond 500 words and update word count display
        contentTextarea.addEventListener('input', function () {
            const content = this.value;
            const words = content.split(/\s+/).filter(word => word.length > 0);
            const wordCount = words.length;
            
            if (wordCount > MAX_WORDS) {
                // Truncate back to 500 words
                const truncatedWords = words.slice(0, MAX_WORDS);
                this.value = truncatedWords.join(' ');
                
                if (wordCountDisplay) {
                    wordCountDisplay.textContent = MAX_WORDS;
                    wordCountDisplay.parentElement.classList.add('reached');
                }
            } else {
                if (wordCountDisplay) {
                    wordCountDisplay.textContent = wordCount;
                    wordCountDisplay.parentElement.classList.remove('reached');
                    
                    // Add warning at 80% (400 words)
                    if (wordCount >= 400) {
                        wordCountDisplay.parentElement.classList.add('warning');
                    } else {
                        wordCountDisplay.parentElement.classList.remove('warning');
                    }
                }
            }
        });
    }

    // File label and preview modal logic
    const fileInput = document.getElementById('avatar');
    const fileLabel = document.getElementById('fileLabel');
    const previewBtn = document.getElementById('filePreviewBtn');
    const removeBtn = document.getElementById('fileRemoveBtn');
    const modal = document.getElementById('filePreviewModal');
    const modalInner = document.getElementById('filePreviewInner') || document.getElementById('filePreviewContent');
    const modalClose = document.getElementById('modalCloseBtn');

    function resetModal() {
        if (modalInner) modalInner.innerHTML = '';
    }

    if (fileInput && fileLabel) {
        // Initialize label
        fileLabel.textContent = 'Pick a file to upload';

        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                const f = this.files[0];
                fileLabel.textContent = f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
                if (previewBtn) previewBtn.disabled = false;
                if (removeBtn) removeBtn.disabled = false;
            } else {
                fileLabel.textContent = 'Pick a file to upload';
                if (previewBtn) previewBtn.disabled = true;
                if (removeBtn) removeBtn.disabled = true;
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (!fileInput) return;
            fileInput.value = '';
            if (fileLabel) fileLabel.textContent = 'Pick a file to upload';
            if (previewBtn) previewBtn.disabled = true;
            removeBtn.disabled = true;
        });
        // start disabled until file selected
        removeBtn.disabled = true;
    }

    if (previewBtn) {
        // start disabled if no file selected
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) previewBtn.disabled = true;

        previewBtn.addEventListener('click', function () {
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;
            const f = fileInput.files[0];
            resetModal();
            const type = f.type || '';
            const reader = new FileReader();

            if (type.startsWith('image/')) {
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    modalInner.appendChild(img);
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                };
                reader.readAsDataURL(f);
            } else if (type.startsWith('video/')) {
                reader.onload = function (e) {
                    const video = document.createElement('video');
                    video.src = e.target.result;
                    video.controls = true;
                    modalInner.appendChild(video);
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                };
                reader.readAsDataURL(f);
            } else {
                // Other types: show filename and download link
                const info = document.createElement('div');
                info.textContent = 'File: ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
                const link = document.createElement('a');
                link.textContent = 'Download';
                link.href = URL.createObjectURL(f);
                link.download = f.name;
                link.style.display = 'inline-block';
                link.style.marginTop = '8px';
                modalInner.appendChild(info);
                modalInner.appendChild(link);
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    }

    // modal close handlers
    if (modalClose) modalClose.addEventListener('click', function () {
        modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true');
    });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) { modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true'); } });
    window.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal && modal.classList.contains('active')) { modal.classList.remove('active'); resetModal(); modal.setAttribute('aria-hidden', 'true'); } });

    // Post detail modal handlers (open full post view)
    const postDetailModal = document.getElementById('postDetailModal');
    const postDetailAvatar = document.getElementById('postDetailAvatar');
    const postDetailUser = document.getElementById('postDetailUser');
    const postDetailTime = document.getElementById('postDetailTime');
    const postDetailContent = document.getElementById('postDetailContent');
    const postDetailMedia = document.getElementById('postDetailMedia');
    const postDetailClose = document.getElementById('postDetailClose');

    function resetPostDetail() {
        if (postDetailContent) postDetailContent.innerHTML = '';
        if (postDetailMedia) postDetailMedia.innerHTML = '';
        if (postDetailAvatar) postDetailAvatar.textContent = '';
        if (postDetailUser) postDetailUser.textContent = '';
        if (postDetailTime) postDetailTime.textContent = '';
    }

    document.querySelectorAll('.view-post-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const raw = this.getAttribute('data-post');
            if (!raw) return;
            let payload = null;
            try { payload = JSON.parse(raw); } catch (err) { console.error('Invalid post payload', err); return; }

            const uname = payload.username || 'User';
            const initial = uname && uname.length ? uname.charAt(0).toUpperCase() : 'U';
            if (postDetailAvatar) postDetailAvatar.textContent = initial;
            if (postDetailUser) postDetailUser.textContent = uname;
            if (postDetailTime) postDetailTime.textContent = payload.created_at ? payload.created_at : '';
            if (postDetailContent) postDetailContent.innerHTML = payload.content ? payload.content.replace(/\n/g, '<br>') : '';

            if (postDetailMedia) {
                postDetailMedia.innerHTML = '';
                if (payload.file_path) {
                    const ext = String(payload.file_path).split('.').pop().toLowerCase();
                    if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                        const img = document.createElement('img');
                        img.src = payload.file_path;
                        img.style.maxWidth = '100%';
                        img.style.borderRadius = '8px';
                        postDetailMedia.appendChild(img);
                    } else if (['mp4','webm','ogg'].includes(ext)) {
                        const video = document.createElement('video');
                        video.src = payload.file_path;
                        video.controls = true;
                        video.style.maxWidth = '100%';
                        postDetailMedia.appendChild(video);
                    } else {
                        const a = document.createElement('a');
                        a.href = payload.file_path;
                        a.target = '_blank';
                        a.textContent = 'Open attachment';
                        postDetailMedia.appendChild(a);
                    }
                }
            }

            if (postDetailModal) { postDetailModal.classList.add('active'); postDetailModal.setAttribute('aria-hidden', 'false'); }
        });
    });

    if (postDetailClose) postDetailClose.addEventListener('click', function () { if (postDetailModal) { postDetailModal.classList.remove('active'); resetPostDetail(); postDetailModal.setAttribute('aria-hidden', 'true'); } });
    if (postDetailModal) postDetailModal.addEventListener('click', function (e) { if (e.target === postDetailModal) { postDetailModal.classList.remove('active'); resetPostDetail(); postDetailModal.setAttribute('aria-hidden', 'true'); } });
    window.addEventListener('keydown', function (e) { if (e.key === 'Escape' && postDetailModal && postDetailModal.classList.contains('active')) { postDetailModal.classList.remove('active'); resetPostDetail(); postDetailModal.setAttribute('aria-hidden', 'true'); } });
});

