<?php
require_once 'auth.php';
require_admin();

$edit_mode = false;
$kuliner = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $id = intval($_GET['id']);
    $kuliner = get_kuliner($id);
    if (!$kuliner)
        redirect('kuliner.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $kategori = clean_input($_POST['kategori']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $deskripsi_lengkap = clean_input($_POST['deskripsi_lengkap']);
    $cita_rasa = clean_input($_POST['cita_rasa']);
    $bahan_utama = clean_input($_POST['bahan_utama']);
    $harga_kisaran = clean_input($_POST['harga_kisaran']);
    $tempat_rekomendasi = clean_input($_POST['tempat_rekomendasi']);
    $status = clean_input($_POST['status']);

    $gambar_utama = '';
    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] == 0) {
        $upload_result = upload_image($_FILES['gambar_file'], 'kuliner');
        if ($upload_result['success']) {
            $gambar_utama = $upload_result['path'];
            if ($edit_mode && $kuliner['gambar_utama'] && strpos($kuliner['gambar_utama'], 'uploads/') === 0) {
                delete_image($kuliner['gambar_utama']);
            }
        }
    } elseif (isset($_POST['gambar_url']) && !empty($_POST['gambar_url'])) {
        $gambar_utama = clean_input($_POST['gambar_url']);
    } elseif ($edit_mode) {
        $gambar_utama = $kuliner['gambar_utama'];
    }

    if ($edit_mode) {
        $query = "UPDATE kuliner SET nama='$nama', kategori='$kategori', deskripsi='$deskripsi', deskripsi_lengkap='$deskripsi_lengkap', 
                  gambar_utama='$gambar_utama', cita_rasa='$cita_rasa', bahan_utama='$bahan_utama', harga_kisaran='$harga_kisaran', 
                  tempat_rekomendasi='$tempat_rekomendasi', status='$status' WHERE id = $id";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Kuliner berhasil diupdate!";
        log_audit('update_kuliner', "Mengubah kuliner \"$nama\".", $id, $nama);
    } else {
        $query = "INSERT INTO kuliner (nama, kategori, deskripsi, deskripsi_lengkap, gambar_utama, cita_rasa, bahan_utama, harga_kisaran, tempat_rekomendasi, status) 
                  VALUES ('$nama', '$kategori', '$deskripsi', '$deskripsi_lengkap', '$gambar_utama', '$cita_rasa', '$bahan_utama', '$harga_kisaran', '$tempat_rekomendasi', '$status')";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Kuliner berhasil ditambahkan!";
        $new_id = mysqli_insert_id($conn);
        log_audit('create_kuliner', "Menambahkan kuliner \"$nama\".", $new_id, $nama);
    }
    redirect('kuliner.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Kuliner - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Kuliner & UMKM</h1>
                <a href="kuliner.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Kuliner/UMKM *</label>
                            <input type="text" id="nama" name="nama" required
                                value="<?php echo $edit_mode ? htmlspecialchars($kuliner['nama']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="kategori">Kategori *</label>
                            <select id="kategori" name="kategori" required>
                                <option value="kuliner" <?php echo ($edit_mode && $kuliner['kategori'] == 'kuliner') ? 'selected' : ''; ?>>Kuliner</option>
                                <option value="umkm" <?php echo ($edit_mode && $kuliner['kategori'] == 'umkm') ? 'selected' : ''; ?>>UMKM</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Singkat *</label>
                        <textarea id="deskripsi" name="deskripsi" rows="2"
                            required><?php echo $edit_mode ? htmlspecialchars($kuliner['deskripsi']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi_lengkap">Deskripsi Lengkap *</label>
                        <textarea id="deskripsi_lengkap" name="deskripsi_lengkap" rows="5"
                            required><?php echo $edit_mode ? htmlspecialchars($kuliner['deskripsi_lengkap']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Gambar Utama *</label>
                        <?php if ($edit_mode && $kuliner['gambar_utama']): ?>
                            <div style="margin-bottom: 15px;">
                                <img src="../<?php echo htmlspecialchars($kuliner['gambar_utama']); ?>" alt="Current"
                                    style="max-width: 300px; border-radius: 5px; border: 2px solid #ddd;">
                                <p style="margin-top: 5px; color: #7f8c8d; font-size: 0.9rem;">Gambar saat ini</p>
                            </div>
                        <?php endif; ?>

                        <div class="upload-option">
                            <label class="radio-inline">
                                <input type="radio" name="upload_method" value="file" checked
                                    onchange="toggleUploadMethod()"> Upload File
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="upload_method" value="url" onchange="toggleUploadMethod()">
                                Pakai URL
                            </label>
                        </div>

                        <div id="file_upload_section">
                            <input type="file" name="gambar_file" id="gambar_file" accept="image/*">
                            <small>Format: JPG, PNG, GIF - Maksimal 5MB</small>
                        </div>

                        <div id="url_upload_section" style="display: none;">
                            <input type="text" name="gambar_url" id="gambar_url" placeholder="Masukkan URL gambar">
                        </div>
                    </div>

                    <script>
                        function toggleUploadMethod() {
                            const method = document.querySelector('input[name="upload_method"]:checked').value;
                            const fileSection = document.getElementById('file_upload_section');
                            const urlSection = document.getElementById('url_upload_section');

                            if (method === 'file') {
                                fileSection.style.display = 'block';
                                urlSection.style.display = 'none';
                            } else {
                                fileSection.style.display = 'none';
                                urlSection.style.display = 'block';
                            }
                        }
                    </script>

                    <div class="form-group">
                        <label for="cita_rasa">Cita Rasa *</label>
                        <input type="text" id="cita_rasa" name="cita_rasa" required
                            value="<?php echo $edit_mode ? htmlspecialchars($kuliner['cita_rasa']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="bahan_utama">Bahan Utama *</label>
                        <input type="text" id="bahan_utama" name="bahan_utama" required
                            value="<?php echo $edit_mode ? htmlspecialchars($kuliner['bahan_utama']) : ''; ?>">
                        <small>Pisahkan dengan koma</small>
                    </div>

                    <div class="form-group">
                        <label for="harga_kisaran">Harga Kisaran *</label>
                        <input type="text" id="harga_kisaran" name="harga_kisaran" required
                            value="<?php echo $edit_mode ? htmlspecialchars($kuliner['harga_kisaran']) : ''; ?>">
                        <small>Contoh: Rp 5.000 - Rp 10.000 / buah</small>
                    </div>

                    <div class="form-group">
                        <label for="tempat_rekomendasi">Tempat Rekomendasi</label>
                        <textarea id="tempat_rekomendasi" name="tempat_rekomendasi"
                            rows="3"><?php echo $edit_mode ? htmlspecialchars($kuliner['tempat_rekomendasi']) : ''; ?></textarea>
                        <small>Pisahkan dengan "|" (pipe). Contoh: Pasar Tradisional|Toko Oleh-Oleh</small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="aktif" <?php echo ($edit_mode && $kuliner['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($edit_mode && $kuliner['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                            <?php echo $edit_mode ? 'Update' : 'Simpan'; ?></button>
                        <a href="kuliner.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>