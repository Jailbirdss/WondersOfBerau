<?php
require_once 'auth.php';
require_admin();

$ref_id = isset($_GET['ref_id']) ? intval($_GET['ref_id']) : 0;
$ref_type = isset($_GET['ref_type']) ? clean_input($_GET['ref_type']) : '';

if (!$ref_id || !$ref_type) {
    redirect('index.php');
}

$ref_data = null;
$ref_name = '';
if ($ref_type == 'destinasi') {
    $ref_data = get_destinasi($ref_id);
    $ref_name = $ref_data ? $ref_data['nama'] : '';
} elseif ($ref_type == 'akomodasi') {
    $ref_data = get_akomodasi($ref_id);
    $ref_name = $ref_data ? $ref_data['nama'] : '';
} elseif ($ref_type == 'kuliner') {
    $ref_data = get_kuliner($ref_id);
    $ref_name = $ref_data ? $ref_data['nama'] : '';
} elseif ($ref_type == 'event') {
    $ref_data = get_events($ref_id);
    $ref_name = $ref_data ? $ref_data['nama'] : '';
}

if (!$ref_data) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload') {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_result = upload_image($_FILES['foto'], 'gallery');
        if ($upload_result['success']) {
            $caption = clean_input($_POST['caption']);
            $urutan = intval($_POST['urutan']);
            $gambar_path = $upload_result['path'];

            $query = "INSERT INTO gallery (ref_id, ref_type, gambar, caption, urutan) 
                      VALUES ($ref_id, '$ref_type', '$gambar_path', '$caption', $urutan)";

            if (mysqli_query($conn, $query)) {
                $_SESSION['success_message'] = "Foto berhasil diupload!";
            } else {
                $_SESSION['error_message'] = "Gagal menyimpan data foto!";
            }
        } else {
            $_SESSION['error_message'] = $upload_result['message'];
        }
        redirect("gallery.php?ref_id=$ref_id&ref_type=$ref_type");
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "SELECT gambar FROM gallery WHERE id = $id");
    if ($foto = mysqli_fetch_assoc($query)) {
        delete_image($foto['gambar']);
        mysqli_query($conn, "DELETE FROM gallery WHERE id = $id");
        $_SESSION['success_message'] = "Foto berhasil dihapus!";
    }
    redirect("gallery.php?ref_id=$ref_id&ref_type=$ref_type");
}

$photos = get_gallery($ref_id, $ref_type);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri - <?php echo htmlspecialchars($ref_name); ?></title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <div>
                    <h1>Kelola Galeri Foto</h1>
                    <p class="subtitle"><?php echo ucfirst($ref_type); ?>:
                        <?php echo htmlspecialchars($ref_name); ?>
                    </p>
                </div>
                <a href="<?php echo $ref_type == 'event' ? 'events' : $ref_type; ?>.php" class="btn btn-back"><i
                        class="fas fa-arrow-left"></i>
                    Kembali</a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <div class="upload-form">
                <h3><i class="fas fa-cloud-upload-alt"></i> Upload Foto Baru</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="foto">Pilih Foto *</label>
                            <input type="file" id="foto" name="foto" accept="image/*" required
                                onchange="previewImage(this)">
                            <small>Format: JPG, PNG, GIF - Maksimal 5MB</small>
                        </div>

                        <div class="form-group">
                            <label for="caption">Caption</label>
                            <input type="text" id="caption" name="caption" placeholder="Deskripsi foto...">
                        </div>

                        <div class="form-group">
                            <label for="urutan">Urutan</label>
                            <input type="number" id="urutan" name="urutan" value="<?php echo count($photos) + 1; ?>"
                                min="0">
                        </div>
                    </div>

                    <div class="image-preview" id="imagePreview">
                        <p><strong>Preview:</strong></p>
                        <img id="previewImg" src="" alt="Preview">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Foto
                    </button>
                </form>
            </div>

            <div class="table-container">
                <h3 class="gallery-heading">Galeri Foto (<?php echo count($photos); ?> foto)</h3>

                <?php if (count($photos) > 0): ?>
                    <div class="gallery-grid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="gallery-item">
                                <img src="../<?php echo htmlspecialchars($photo['gambar']); ?>"
                                    alt="<?php echo htmlspecialchars($photo['caption']); ?>">
                                <div class="gallery-item-info">
                                    <p><strong>Caption:</strong> <?php echo htmlspecialchars($photo['caption']) ?: '-'; ?></p>
                                    <p><strong>Urutan:</strong> <?php echo $photo['urutan']; ?></p>
                                </div>
                                <div class="gallery-item-actions">
                                    <a href="?ref_id=<?php echo $ref_id; ?>&ref_type=<?php echo $ref_type; ?>&action=delete&id=<?php echo $photo['id']; ?>"
                                        class="btn btn-sm btn-danger" data-confirm="Yakin hapus foto ini?">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Belum ada foto. Upload foto pertama Anda!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>