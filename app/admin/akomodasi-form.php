<?php
require_once 'auth.php';
require_admin();

$edit_mode = false;
$akomodasi = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $id = intval($_GET['id']);
    $akomodasi = get_akomodasi($id);
    if (!$akomodasi)
        redirect('akomodasi.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $tipe = clean_input($_POST['tipe']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $deskripsi_lengkap = clean_input($_POST['deskripsi_lengkap']);
    $lokasi = clean_input($_POST['lokasi']);
    $harga_mulai = clean_input($_POST['harga_mulai']);
    $fasilitas = clean_input($_POST['fasilitas']);
    $tipe_kamar = clean_input($_POST['tipe_kamar']);
    $telepon = clean_input($_POST['telepon']);
    $email = clean_input($_POST['email']);
    $website = clean_input($_POST['website']);
    $maps_embed = clean_input($_POST['maps_embed']);
    $status = clean_input($_POST['status']);

    $gambar_utama = '';
    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] == 0) {
        $upload_result = upload_image($_FILES['gambar_file'], 'akomodasi');
        if ($upload_result['success']) {
            $gambar_utama = $upload_result['path'];
            if ($edit_mode && $akomodasi['gambar_utama'] && strpos($akomodasi['gambar_utama'], 'uploads/') === 0) {
                delete_image($akomodasi['gambar_utama']);
            }
        }
    } elseif (isset($_POST['gambar_url']) && !empty($_POST['gambar_url'])) {
        $gambar_utama = clean_input($_POST['gambar_url']);
    } elseif ($edit_mode) {
        $gambar_utama = $akomodasi['gambar_utama'];
    }

    if ($edit_mode) {
        $query = "UPDATE akomodasi SET nama='$nama', tipe='$tipe', deskripsi='$deskripsi', deskripsi_lengkap='$deskripsi_lengkap', 
                  lokasi='$lokasi', gambar_utama='$gambar_utama', harga_mulai='$harga_mulai', fasilitas='$fasilitas', 
                  tipe_kamar='$tipe_kamar', telepon='$telepon', email='$email', website='$website', maps_embed='$maps_embed', status='$status' 
                  WHERE id = $id";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Akomodasi berhasil diupdate!";
        log_audit('update_akomodasi', "Mengubah akomodasi \"$nama\".", $id, $nama);
    } else {
        $query = "INSERT INTO akomodasi (nama, tipe, deskripsi, deskripsi_lengkap, lokasi, gambar_utama, harga_mulai, fasilitas, tipe_kamar, telepon, email, website, maps_embed, status) 
                  VALUES ('$nama', '$tipe', '$deskripsi', '$deskripsi_lengkap', '$lokasi', '$gambar_utama', '$harga_mulai', '$fasilitas', '$tipe_kamar', '$telepon', '$email', '$website', '$maps_embed', '$status')";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Akomodasi berhasil ditambahkan!";
        $new_id = mysqli_insert_id($conn);
        log_audit('create_akomodasi', "Menambahkan akomodasi \"$nama\".", $new_id, $nama);
    }
    redirect('akomodasi.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Akomodasi - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Akomodasi</h1>
                <a href="akomodasi.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Akomodasi *</label>
                            <input type="text" id="nama" name="nama" required
                                value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['nama']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="tipe">Tipe *</label>
                            <select id="tipe" name="tipe" required>
                                <option value="hotel" <?php echo ($edit_mode && $akomodasi['tipe'] == 'hotel') ? 'selected' : ''; ?>>Hotel</option>
                                <option value="resort" <?php echo ($edit_mode && $akomodasi['tipe'] == 'resort') ? 'selected' : ''; ?>>Resort</option>
                                <option value="homestay" <?php echo ($edit_mode && $akomodasi['tipe'] == 'homestay') ? 'selected' : ''; ?>>Homestay</option>
                                <option value="villa" <?php echo ($edit_mode && $akomodasi['tipe'] == 'villa') ? 'selected' : ''; ?>>Villa</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Singkat *</label>
                        <textarea id="deskripsi" name="deskripsi" rows="2"
                            required><?php echo $edit_mode ? htmlspecialchars($akomodasi['deskripsi']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi_lengkap">Deskripsi Lengkap *</label>
                        <textarea id="deskripsi_lengkap" name="deskripsi_lengkap" rows="5"
                            required><?php echo $edit_mode ? htmlspecialchars($akomodasi['deskripsi_lengkap']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="lokasi">Lokasi *</label>
                            <input type="text" id="lokasi" name="lokasi" required
                                value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['lokasi']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="harga_mulai">Harga Mulai *</label>
                            <input type="text" id="harga_mulai" name="harga_mulai" required
                                value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['harga_mulai']) : ''; ?>">
                            <small>Contoh: Mulai dari Rp 500.000 / malam</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Gambar Utama *</label>
                        <?php if ($edit_mode && $akomodasi['gambar_utama']): ?>
                            <div style="margin-bottom: 15px;">
                                <img src="../<?php echo htmlspecialchars($akomodasi['gambar_utama']); ?>" alt="Current"
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
                        <label for="fasilitas">Fasilitas *</label>
                        <input type="text" id="fasilitas" name="fasilitas" required
                            value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['fasilitas']) : ''; ?>">
                        <small>Pisahkan dengan koma</small>
                    </div>

                    <div class="form-group">
                        <label for="tipe_kamar">Tipe Kamar *</label>
                        <input type="text" id="tipe_kamar" name="tipe_kamar" required
                            value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['tipe_kamar']) : ''; ?>">
                        <small>Pisahkan dengan koma</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telepon">Telepon *</label>
                            <input type="text" id="telepon" name="telepon" required
                                value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['telepon']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required
                                value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website"
                            value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['website']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="maps_embed">Google Maps Embed URL</label>
                        <input type="text" id="maps_embed" name="maps_embed"
                            value="<?php echo $edit_mode ? htmlspecialchars($akomodasi['maps_embed']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="aktif" <?php echo ($edit_mode && $akomodasi['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($edit_mode && $akomodasi['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                            <?php echo $edit_mode ? 'Update' : 'Simpan'; ?></button>
                        <a href="akomodasi.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>