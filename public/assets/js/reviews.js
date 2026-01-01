(function () {
    const sections = document.querySelectorAll('[data-review-section]');
    if (!sections.length) return;

    const API_URL = 'api/review.php';
    let photoLightbox = null;

    const ensurePhotoLightbox = () => {
        if (photoLightbox) return photoLightbox;
        const overlay = document.createElement('div');
        overlay.className = 'photo-lightbox';
        overlay.innerHTML = `
            <div class="photo-lightbox__backdrop"></div>
            <div class="photo-lightbox__body">
                <button class="photo-lightbox__close" type="button" aria-label="Tutup pratinjau">&times;</button>
                <img class="photo-lightbox__img" src="" alt="Foto ulasan">
            </div>
        `;
        document.body.appendChild(overlay);

        const imgEl = overlay.querySelector('.photo-lightbox__img');
        const closeBtn = overlay.querySelector('.photo-lightbox__close');
        const close = () => {
            overlay.classList.remove('active');
            document.body.classList.remove('lightbox-open');
        };
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.classList.contains('photo-lightbox__backdrop')) {
                close();
            }
        });
        closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
                close();
            }
        });

        photoLightbox = {
            show(src) {
                imgEl.src = src;
                overlay.classList.add('active');
                document.body.classList.add('lightbox-open');
            },
            hide: close
        };
        return photoLightbox;
    };

    const getDeviceToken = () => {
        try {
            const saved = localStorage.getItem('wb_device_token');
            if (saved) return saved;
            const token = (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2)) + Date.now();
            localStorage.setItem('wb_device_token', token);
            return token;
        } catch (e) {
            return (Math.random().toString(36).slice(2)) + Date.now();
        }
    };

    const deviceToken = getDeviceToken();

    const buildStars = (value) => {
        const container = document.createElement('div');
        container.className = 'stars-inline';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('span');
            star.className = 'star' + (i <= value ? ' filled' : '');
            star.textContent = '★';
            container.appendChild(star);
        }
        return container;
    };

    const setFeedback = (el, message, isSuccess = false) => {
        if (!el) return;
        el.textContent = message || '';
        el.classList.remove('error', 'success');
        if (message) {
            el.classList.add(isSuccess ? 'success' : 'error');
        }
    };

    const setLocked = (form, lock, state) => {
        const editBtn = form.querySelector('.edit-review-btn');
        const submitBtn = form.querySelector('.submit-review');
        const feedback = form.querySelector('.review-feedback');
        form.classList.toggle('locked', lock);
        form.querySelectorAll('input, textarea, button').forEach(el => {
            if (el === editBtn) return;
            if (el.type === 'submit') {
                el.disabled = lock;
            } else {
                el.disabled = lock;
            }
        });
        if (editBtn) {
            editBtn.hidden = !state.hasReview;
            editBtn.disabled = false;
        }
        if (lock && state.hasReview) {
            setFeedback(feedback, 'Ulasanmu sudah tersimpan. Klik "Ubah ulasan" kalau mau mengganti.', true);
            if (submitBtn) submitBtn.disabled = true;
        }
    };

    const renderSummary = (section, summary) => {
        const avgEl = section.querySelector('.review-average');
        const totalEl = section.querySelector('.review-total');
        const summaryStars = section.querySelectorAll('.review-summary-card .star');
        const average = summary?.average || 0;
        const count = summary?.count || 0;
        if (avgEl) avgEl.textContent = average.toFixed(1);
        if (totalEl) totalEl.textContent = count.toString();
        summaryStars.forEach((star, idx) => {
            const portion = Math.max(0, Math.min(1, average - idx));
            const fill = Math.round(portion * 100);
            star.style.setProperty('--fill', `${fill}%`);
            star.classList.toggle('filled', fill >= 100);
            star.classList.toggle('partial', fill > 0 && fill < 100);
        });
    };

    const renderReviews = (section, reviews) => {
        const list = section.querySelector('.review-list');
        if (!list) return;
        list.innerHTML = '';

        const maskName = (name) => {
            const clean = (name || '').trim();
            if (!clean) return 'Pengunjung';
            if (clean.length <= 2) return clean[0] + '*';
            if (clean.length === 3) return clean[0] + '*'.repeat(1) + clean[2];
            const head = clean.slice(0, 2);
            const tail = clean.slice(-1);
            const masked = head + '*'.repeat(Math.max(1, clean.length - 3)) + tail;
            return masked;
        };

        if (!reviews || !reviews.length) {
            const empty = document.createElement('div');
            empty.className = 'review-empty';
            empty.textContent = list.dataset.emptyState || 'Belum ada ulasan untuk konten ini.';
            list.appendChild(empty);
            return;
        }

        reviews.forEach((rev) => {
            const item = document.createElement('div');
            item.className = 'review-item';

            const head = document.createElement('div');
            head.className = 'review-item-head';

            const nameBlock = document.createElement('div');
            nameBlock.className = 'review-name';
            nameBlock.textContent = maskName(rev.name);
            head.appendChild(nameBlock);

            const meta = document.createElement('div');
            meta.className = 'review-meta';
            const stars = buildStars(rev.rating || 0);
            stars.setAttribute('aria-label', `Rating ${rev.rating || 0} dari 5`);
            meta.appendChild(stars);
            const date = document.createElement('span');
            date.className = 'review-date';
            date.textContent = rev.date_label || '';
            meta.appendChild(date);
            head.appendChild(meta);
            item.appendChild(head);

            const comment = document.createElement('p');
            comment.className = 'review-comment';
            comment.textContent = rev.comment || 'Tanpa komentar';
            item.appendChild(comment);

            if (rev.photo) {
                const photoWrap = document.createElement('button');
                photoWrap.type = 'button';
                photoWrap.className = 'review-photo-thumb';
                const thumb = document.createElement('img');
                thumb.src = rev.photo;
                thumb.alt = 'Foto ulasan';
                thumb.loading = 'lazy';
                const label = document.createElement('span');
                label.className = 'photo-label';
                label.textContent = 'Lihat foto';
                photoWrap.appendChild(thumb);
                photoWrap.appendChild(label);
                photoWrap.addEventListener('click', () => {
                    ensurePhotoLightbox().show(rev.photo);
                });
                item.appendChild(photoWrap);
            }

            list.appendChild(item);
        });
    };

    const prefillForm = (form, deviceReview) => {
        if (!deviceReview || !form) return;
        const nameInput = form.querySelector('input[name="name"]');
        const ratingInput = form.querySelector('input[name="rating"]');
        const ratingBtns = form.querySelectorAll('.rating-star');
        const comment = form.querySelector('textarea[name="comment"]');
        const fileInput = form.querySelector('input[type="file"]');
        const previewWrap = form.querySelector('.review-photo-preview');
        const previewImg = previewWrap ? previewWrap.querySelector('img') : null;
        const removePhotoInput = form.querySelector('input[name="remove_photo"]');
        const previewLabel = previewWrap ? previewWrap.querySelector('.preview-label') : null;

        if (nameInput) nameInput.value = deviceReview.name || '';
        if (ratingInput) ratingInput.value = deviceReview.rating || 0;
        ratingBtns.forEach((b) => {
            const active = parseInt(b.dataset.value, 10) <= (deviceReview.rating || 0);
            b.classList.toggle('active', active);
            b.setAttribute('aria-checked', active ? 'true' : 'false');
        });
        if (comment && typeof deviceReview.comment === 'string') {
            comment.value = deviceReview.comment;
        }

        if (deviceReview.photo && previewWrap && previewImg) {
            previewImg.src = deviceReview.photo;
            previewWrap.hidden = false;
            if (previewLabel) previewLabel.hidden = false;
            if (removePhotoInput) removePhotoInput.value = '0';
            if (fileInput) fileInput.value = '';
        } else if (previewWrap) {
            previewWrap.hidden = true;
            if (previewLabel) previewLabel.hidden = true;
            if (removePhotoInput) removePhotoInput.value = '0';
        }
    };

    const renderPagination = (section, pagination, state, reloadFn) => {
        const container = section.querySelector('.review-pagination');
        if (!container) return;
        const firstBtn = container.querySelector('.page-first');
        const prevBtn = container.querySelector('.page-prev');
        const nextBtn = container.querySelector('.page-next');
        const lastBtn = container.querySelector('.page-last');
        const info = container.querySelector('.page-info');
        const total = pagination?.total || 0;
        const totalPages = pagination?.total_pages || 1;
        const page = pagination?.page || 1;

        if (total <= state.perPage) {
            container.hidden = true;
            return;
        }

        container.hidden = false;
        info.textContent = `Halaman ${page} / ${totalPages}`;
        const disableFirstPrev = page <= 1;
        const disableNextLast = page >= totalPages;
        [firstBtn, prevBtn].forEach((btn) => { if (btn) btn.disabled = disableFirstPrev; });
        [nextBtn, lastBtn].forEach((btn) => { if (btn) btn.disabled = disableNextLast; });

        if (firstBtn) {
            firstBtn.onclick = () => {
                if (state.page !== 1) {
                    state.page = 1;
                    reloadFn();
                }
            };
        }

        if (prevBtn) {
            prevBtn.onclick = () => {
                if (state.page > 1) {
                    state.page -= 1;
                    reloadFn();
                }
            };
        }

        if (nextBtn) {
            nextBtn.onclick = () => {
                if (state.page < totalPages) {
                    state.page += 1;
                    reloadFn();
                }
            };
        }

        if (lastBtn) {
            lastBtn.onclick = () => {
                if (state.page !== totalPages) {
                    state.page = totalPages;
                    reloadFn();
                }
            };
        }
    };

    const fetchReviews = async (section, state) => {
        const params = new URLSearchParams({
            type: section.dataset.type,
            id: section.dataset.id,
            device_token: deviceToken,
            page: state.page,
            limit: state.perPage
        });
        if (state.sort) params.set('sort', state.sort);
        if (state.rating) params.set('rating', state.rating);

        try {
            const res = await fetch(`${API_URL}?${params.toString()}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Gagal mengambil ulasan');

            const { summary, reviews, has_reviewed, your_review, pagination } = data.data;
            state.hasReview = !!(has_reviewed || your_review);
            renderSummary(section, summary);
            renderReviews(section, reviews);
            renderPagination(section, pagination, state, () => fetchReviews(section, state));

            const form = section.querySelector('.review-form');
            if (state.hasReview && your_review) {
                prefillForm(form, your_review);
                setLocked(form, true, state);
            } else {
                setLocked(form, false, state);
            }
        } catch (err) {
            const list = section.querySelector('.review-list');
            if (list) {
                list.innerHTML = `<div class="review-empty">Tidak dapat memuat ulasan.</div>`;
            }
        }
    };

    const initForm = (section, state) => {
        const form = section.querySelector('.review-form');
        if (!form) return;

        const nameInput = form.querySelector('input[name="name"]');
        const ratingInput = form.querySelector('input[name="rating"]');
        const ratingBtns = form.querySelectorAll('.rating-star');
        const fileInput = form.querySelector('input[type="file"]');
        const previewWrap = form.querySelector('.review-photo-preview');
        const previewImg = previewWrap ? previewWrap.querySelector('img') : null;
        const clearBtn = previewWrap ? previewWrap.querySelector('.clear-photo') : null;
        const feedback = form.querySelector('.review-feedback');
        const submitBtn = form.querySelector('.submit-review');
        const editBtn = form.querySelector('.edit-review-btn');
        const removePhotoInput = form.querySelector('input[name="remove_photo"]');

        ratingBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const val = parseInt(btn.dataset.value, 10);
                ratingInput.value = val;
                ratingBtns.forEach((b) => {
                    const active = parseInt(b.dataset.value, 10) <= val;
                    b.classList.toggle('active', active);
                    b.setAttribute('aria-checked', active ? 'true' : 'false');
                });
            });
        });

        const resetPreview = () => {
            if (previewWrap) previewWrap.hidden = true;
            if (previewImg) previewImg.src = '';
            if (fileInput) fileInput.value = '';
        };

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (!file || !previewWrap || !previewImg) {
                resetPreview();
                return;
            }
            const url = URL.createObjectURL(file);
            previewImg.src = url;
            previewWrap.hidden = false;
            const previewLabelChange = previewWrap.querySelector('.preview-label');
            if (previewLabelChange) previewLabelChange.hidden = false;
            if (removePhotoInput) removePhotoInput.value = '0';
        });

        clearBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            resetPreview();
            const previewLabel = previewWrap ? previewWrap.querySelector('.preview-label') : null;
            if (previewLabel) previewLabel.hidden = true;
            if (removePhotoInput) removePhotoInput.value = '1';
        });

        editBtn?.addEventListener('click', () => {
            setLocked(form, false, state);
            setFeedback(feedback, 'Silakan perbarui ulasanmu lalu kirim lagi.', true);
            submitBtn?.focus();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const ratingVal = parseInt(ratingInput.value, 10);
            if (!ratingVal || ratingVal < 1) {
                setFeedback(feedback, 'Pilih rating terlebih dahulu.', false);
                return;
            }

            setFeedback(feedback, 'Mengirim ulasan...', true);
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('device_token', deviceToken);

            try {
                const res = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Gagal menyimpan ulasan');
                setFeedback(feedback, data.message || 'Review berhasil dikirim.', true);
                state.hasReview = true;
                setLocked(form, true, state);
                fetchReviews(section, state);
            } catch (err) {
                setFeedback(feedback, err.message || 'Gagal mengirim ulasan', false);
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    };

    const initFilters = (section, state) => {
        const sortSelect = section.querySelector('.review-sort');
        const ratingSelect = section.querySelector('.review-rating-filter');
        const reload = () => fetchReviews(section, state);

        sortSelect?.addEventListener('change', () => {
            state.sort = sortSelect.value || 'newest';
            state.page = 1;
            reload();
        });

        ratingSelect?.addEventListener('change', () => {
            state.rating = ratingSelect.value || '';
            state.page = 1;
            reload();
        });
    };

    sections.forEach((section) => {
        const state = { sort: 'newest', rating: '', hasReview: false, page: 1, perPage: 5 };
        initForm(section, state);
        initFilters(section, state);
        fetchReviews(section, state);
    });
})(); 