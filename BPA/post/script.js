// New: Lazy-loading feed, skeletons, caching, and modal handling
document.addEventListener('DOMContentLoaded', function () {
    const BATCH_SIZE = 10; // 5-10 recommended
    const postsContainer = document.getElementById('posts-container');
    const loadingSpinner = document.getElementById('loading-spinner');
    const fileInput = document.getElementById('avatar');
    const fileLabel = document.getElementById('fileLabel');

    const postsCache = {};
    const postsById = {};
    let currentOffset = 0;
    let isLoading = false;
    let hasMorePosts = true;

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>\"']/g, m => map[m]);
    }

    function createSkeletonPost() {
        const s = document.createElement('div');
        s.className = 'post skeleton-post';
        s.style.cssText = 'background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.08);';
        s.innerHTML = '<div style="height:14px;background:#eaeaea;border-radius:4px;margin-bottom:8px;width:40%;"></div><div style="height:10px;background:#f1f1f1;border-radius:4px;width:80%;margin-bottom:12px;"></div><div style="height:120px;background:#eaeaea;border-radius:6px;"></div>';
        return s;
    }

    function renderPost(post) {
        // cache by id
        if (post.post_id) postsById[String(post.post_id)] = post;

        const o = document.createElement('div');
        o.className = 'post';
        o.style.cssText = 'background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.06);';

        const preview = post.content && post.content.length > 400 ? post.content.substring(0,400) + '...' : (post.content || '');

        let media = '';
        if (post.file_path) {
            const ext = String(post.file_path).split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) media = `<img src="${escapeHtml(post.file_path)}" loading="lazy" style="max-width:220px;border-radius:8px;display:block;margin-top:8px;">`;
            else if (['mp4','webm','ogg'].includes(ext)) media = `<video controls style="max-width:220px;border-radius:8px;display:block;margin-top:8px;"><source src="${escapeHtml(post.file_path)}"></video>`;
            else media = `<a href="${escapeHtml(post.file_path)}" target="_blank">Open attachment</a>`;
        }

                        o.innerHTML = `
                                <div class="post-card-inner">
                                    <div class="post-header">
                                        <div class="post-avatar">${escapeHtml(post.initial||'U')}</div>
                                        <div class="post-meta">
                                            <div class="post-username">${escapeHtml(post.username||'')}</div>
                                            <div class="post-time">${escapeHtml(post.time_ago||'')}</div>
                                        </div>
                                        <div class="post-view"><button type="button" class="view-post-btn" data-post-id="${escapeHtml(String(post.post_id||''))}">View</button></div>
                                    </div>
                                    <div class="post-content">${escapeHtml(preview).replace(/\n/g,'<br>')}</div>
                                    ${media ? `<div class="post-media">${media}</div>` : ''}
                                </div>
                        `;

        return o;
    }

    async function fetchPosts(offset, limit = BATCH_SIZE) {
        if (postsCache[offset]) return postsCache[offset];
        isLoading = true;
        if (loadingSpinner) loadingSpinner.style.display = 'block';
        try {
            const res = await fetch(`get_posts.php?offset=${offset}&limit=${limit}`);
            const data = await res.json();
            if (data && data.success) {
                postsCache[offset] = data;
                if (Array.isArray(data.posts)) data.posts.forEach(p => { if (p.post_id) postsById[String(p.post_id)] = p; });
                hasMorePosts = !!data.has_more;
                return data;
            }
            return null;
        } catch (err) {
            console.error('fetch error', err);
            return null;
        } finally {
            isLoading = false;
            if (loadingSpinner) loadingSpinner.style.display = 'none';
        }
    }

    async function loadInitialPosts() {
        const data = await fetchPosts(0);
        if (!data) { if (postsContainer) postsContainer.innerHTML = '<div style="padding:16px;color:#c00">Error loading posts.</div>'; return; }
        if (data.posts.length === 0) { if (postsContainer) postsContainer.innerHTML = '<div style="padding:16px;">No posts yet.</div>'; hasMorePosts = false; return; }
        data.posts.forEach(p => postsContainer.appendChild(renderPost(p)));
        currentOffset = data.posts.length;
        attachViewPostHandlers();
        setupObserver();
    }

    function setupObserver() {
        const sentinel = document.createElement('div'); sentinel.id = 'scroll-sentinel'; sentinel.style.height = '20px'; postsContainer.appendChild(sentinel);
        const obs = new IntersectionObserver(entries => {
            entries.forEach(en => { if (en.isIntersecting && hasMorePosts && !isLoading) loadNextBatch(); });
        }, { rootMargin: '200px' });
        obs.observe(sentinel);
    }

    async function loadNextBatch() {
        if (isLoading || !hasMorePosts) return;
        for (let i=0;i<BATCH_SIZE;i++) postsContainer.appendChild(createSkeletonPost());
        const sentinel = document.getElementById('scroll-sentinel');
        const data = await fetchPosts(currentOffset);
        document.querySelectorAll('.skeleton-post').forEach(n => n.remove());
        if (data && data.posts.length>0) {
            data.posts.forEach(p => postsContainer.insertBefore(renderPost(p), sentinel));
            currentOffset += data.posts.length;
            attachViewPostHandlers();
        }
    }

    function attachViewPostHandlers() {
        const modal = document.getElementById('postDetailModal');
        const avatar = document.getElementById('postDetailAvatar');
        const userEl = document.getElementById('postDetailUser');
        const timeEl = document.getElementById('postDetailTime');
        const contentEl = document.getElementById('postDetailContent');
        const mediaEl = document.getElementById('postDetailMedia');
        const closeBtn = document.getElementById('postDetailClose');

        document.querySelectorAll('.view-post-btn').forEach(btn => {
            btn.onclick = function(e) {
                const id = this.getAttribute('data-post-id');
                if (!id) return;
                const p = postsById[String(id)];
                if (!p) return;
                if (avatar) avatar.textContent = (p.username && p.username.length)? p.username.charAt(0).toUpperCase() : 'U';
                if (userEl) userEl.textContent = p.username || '';
                if (timeEl) timeEl.textContent = p.time_ago || '';
                if (contentEl) contentEl.innerHTML = p.content ? escapeHtml(p.content).replace(/\n/g,'<br>') : '';
                if (mediaEl) {
                    mediaEl.innerHTML = '';
                    if (p.file_path) {
                        const ext = String(p.file_path).split('.').pop().toLowerCase();
                        if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                            const img = document.createElement('img'); img.src = p.file_path; img.loading='lazy'; img.style.maxWidth='100%'; img.style.borderRadius='8px'; mediaEl.appendChild(img);
                        } else if (['mp4','webm','ogg'].includes(ext)) {
                            const v = document.createElement('video'); v.src = p.file_path; v.controls = true; v.style.maxWidth='100%'; mediaEl.appendChild(v);
                        } else {
                            const a = document.createElement('a'); a.href = p.file_path; a.target = '_blank'; a.textContent = 'Open attachment'; mediaEl.appendChild(a);
                        }
                    }
                }
                if (modal) { modal.classList.add('active'); modal.setAttribute('aria-hidden','false'); }
            };
        });

        if (closeBtn) closeBtn.onclick = function(){ if (modal) { modal.classList.remove('active'); modal.setAttribute('aria-hidden','true'); } };
        if (modal) modal.onclick = function(e){ if (e.target === modal) { modal.classList.remove('active'); modal.setAttribute('aria-hidden','true'); } };
        window.onkeydown = function(e){ if (e.key === 'Escape' && modal && modal.classList.contains('active')) { modal.classList.remove('active'); modal.setAttribute('aria-hidden','true'); } };
    }

    // file input helpers (kept minimal)
    const previewBtn = document.getElementById('filePreviewBtn');
    const removeBtn = document.getElementById('fileRemoveBtn');
    const fileModal = document.getElementById('filePreviewModal');
    const fileModalInner = document.getElementById('filePreviewInner') || document.getElementById('filePreviewContent');
    const fileModalClose = document.getElementById('modalCloseBtn');
    function resetFileModal(){ if (fileModalInner) fileModalInner.innerHTML = ''; }
    if (fileInput && fileLabel) {
        fileLabel.textContent = 'Pick a file to upload';
        fileInput.onchange = function(){ if (this.files && this.files.length>0) { const f = this.files[0]; fileLabel.textContent = f.name + ' ('+Math.round(f.size/1024)+' KB)'; if (previewBtn) previewBtn.disabled = false; if (removeBtn) removeBtn.disabled = false; } else { fileLabel.textContent='Pick a file to upload'; if (previewBtn) previewBtn.disabled=true; if (removeBtn) removeBtn.disabled=true; } };
    }
    if (removeBtn) { removeBtn.onclick = function(){ if (!fileInput) return; fileInput.value=''; if (fileLabel) fileLabel.textContent='Pick a file to upload'; if (previewBtn) previewBtn.disabled=true; this.disabled=true; }; removeBtn.disabled=true; }
    if (previewBtn) { previewBtn.disabled = !(fileInput && fileInput.files && fileInput.files.length>0); previewBtn.onclick = function(){ if (!fileInput || !fileInput.files || fileInput.files.length===0) return; const f = fileInput.files[0]; resetFileModal(); const type = f.type || ''; const reader = new FileReader(); if (type.startsWith('image/')) { reader.onload = function(e){ const img = document.createElement('img'); img.src = e.target.result; fileModalInner.appendChild(img); fileModal.classList.add('active'); fileModal.setAttribute('aria-hidden','false'); }; reader.readAsDataURL(f); } else if (type.startsWith('video/')) { reader.onload = function(e){ const v = document.createElement('video'); v.src = e.target.result; v.controls = true; fileModalInner.appendChild(v); fileModal.classList.add('active'); fileModal.setAttribute('aria-hidden','false'); }; reader.readAsDataURL(f); } else { const info = document.createElement('div'); info.textContent = 'File: '+f.name+' ('+Math.round(f.size/1024)+' KB)'; const link = document.createElement('a'); link.textContent='Download'; link.href = URL.createObjectURL(f); link.download = f.name; link.style.display='inline-block'; link.style.marginTop='8px'; fileModalInner.appendChild(info); fileModalInner.appendChild(link); fileModal.classList.add('active'); fileModal.setAttribute('aria-hidden','false'); } };
    }
    if (fileModalClose) fileModalClose.onclick = function(){ if (fileModal) { fileModal.classList.remove('active'); resetFileModal(); fileModal.setAttribute('aria-hidden','true'); } };
    if (fileModal) fileModal.onclick = function(e){ if (e.target === fileModal) { fileModal.classList.remove('active'); resetFileModal(); fileModal.setAttribute('aria-hidden','true'); } };

    // Start
    if (postsContainer) loadInitialPosts();
});

