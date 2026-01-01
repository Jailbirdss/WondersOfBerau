<?php
if (!isset($refType, $itemId, $itemName)) {
    return;
}

$textareaId = 'review-comment-' . intval($itemId);
$fileId = 'review-photo-' . intval($itemId);
?>
<section class="container section review-section" data-review-section data-type="<?php echo htmlspecialchars($refType); ?>" data-id="<?php echo intval($itemId); ?>" data-name="<?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="review-heading">
        <div class="review-heading-text">
            <h2 class="section-title" style="text-align: left;">Bagaimana pengalamanmu di <?php echo htmlspecialchars($itemName); ?>?</h2>
            <p class="review-hint">Gimana pengalamanmu? Ceritain singkat aja ya. Komentar & foto opsional, dan ulasanmu bisa kamu edit kapan saja.</p>
        </div>
        <div class="review-summary-card">
            <div class="review-score">
                <span class="review-average">0.0</span>
                <div class="review-stars" aria-label="Rata-rata 0 dari 5">
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                    <span class="star">&#9733;</span>
                </div>
            </div>
            <div class="review-count"><span class="review-total">0</span> ulasan</div>
        </div>
    </div>

    <div class="review-grid">
        <div class="review-card review-form-card">
            <div class="review-card-head">
                <div>
                    <h3>Kirim pendapatmu</h3>
                </div>
                <span class="device-badge">Maks. 1 ulasan</span>
            </div>
            <form class="review-form" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($refType); ?>">
                <input type="hidden" name="id" value="<?php echo intval($itemId); ?>">
                <input type="hidden" name="rating" value="0">
                <input type="hidden" name="remove_photo" value="0">

                <label class="form-label" for="review-name-<?php echo intval($itemId); ?>">Nama <span class="label-required">*</span></label>
                <input id="review-name-<?php echo intval($itemId); ?>" type="text" name="name" placeholder="Tulis namamu" required>

                <label class="form-label">Rating kamu <span class="label-required">*</span></label>
                <div class="rating-input" aria-label="Pilih rating" role="radiogroup">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rating-star" data-value="<?php echo $i; ?>" aria-label="<?php echo $i; ?> bintang">
                            <i class="fa-solid fa-star"></i>
                        </button>
                    <?php endfor; ?>
                </div>

                <label class="form-label" for="<?php echo $textareaId; ?>">Komentar</label>
                <textarea id="<?php echo $textareaId; ?>" name="comment" rows="3" placeholder="Ceritakan pengalamanmu..."></textarea>

                <label class="form-label" for="<?php echo $fileId; ?>">Foto pendukung</label>
                <input id="<?php echo $fileId; ?>" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                <div class="review-photo-preview" hidden>
                    <span class="preview-label">Pratinjau foto</span>
                    <img src="" alt="Pratinjau foto" loading="lazy">
                    <button type="button" class="clear-photo">Hapus</button>
                </div>

                <div class="review-feedback" role="status"></div>
                <div class="review-actions">
                    <button type="submit" class="btn btn-primary submit-review">Kirim ulasan</button>
                    <button type="button" class="btn btn-secondary edit-review-btn" hidden>Ubah ulasan</button>
                </div>
            </form>
        </div>

        <div class="review-card review-list-card">
            <div class="review-card-head">
                <div>
                    <h3>Yang mereka rasakan</h3>
                </div>
                <div class="review-filters">
                    <select class="review-sort" aria-label="Urutkan ulasan">
                        <option value="newest" selected>Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="highest">Rating tertinggi</option>
                        <option value="lowest">Rating terendah</option>
                    </select>
                    <select class="review-rating-filter" aria-label="Filter rating">
                        <option value="">Semua bintang</option>
                        <option value="5">5 bintang</option>
                        <option value="4">4 bintang</option>
                        <option value="3">3 bintang</option>
                        <option value="2">2 bintang</option>
                        <option value="1">1 bintang</option>
                    </select>
                </div>
            </div>
            <div class="review-list" data-empty-state="Belum ada ulasan untuk konten ini. Jadilah yang pertama!"></div>
            <div class="review-pagination" hidden>
                <button type="button" class="page-first" aria-label="Halaman pertama">&#171;</button>
                <button type="button" class="page-prev" aria-label="Halaman sebelumnya">&#8249;</button>
                <span class="page-info" aria-live="polite">Halaman 1 / 1</span>
                <button type="button" class="page-next" aria-label="Halaman berikutnya">&#8250;</button>
                <button type="button" class="page-last" aria-label="Halaman terakhir">&#187;</button>
            </div>
        </div>
    </div>
</section>