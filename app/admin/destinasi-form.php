<?php
require_once 'auth.php';
require_admin();

$edit_mode = false;
$destinasi = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $id = intval($_GET['id']);
    $destinasi = get_destinasi($id);
    
    if (!$destinasi) {
        redirect('destinasi.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $deskripsi_lengkap = clean_input($_POST['deskripsi_lengkap']);
    $lokasi = clean_input($_POST['lokasi']);
    $tiket_masuk = clean_input($_POST['tiket_masuk']);
    $fasilitas = clean_input($_POST['fasilitas']);
    $jam_buka = clean_input($_POST['jam_buka']);
    $dos = clean_input($_POST['dos']);
    $donts = clean_input($_POST['donts']);
    $maps_embed = clean_input($_POST['maps_embed']);
    $status = clean_input($_POST['status']);
    
    $gambar_utama = '';
    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] == 0) {
        $upload_result = upload_image($_FILES['gambar_file'], 'destinasi');
        if ($upload_result['success']) {
            $gambar_utama = $upload_result['path'];
            if ($edit_mode && !empty($destinasi['gambar_utama']) && strpos($destinasi['gambar_utama'], 'uploads/') === 0) {
                delete_image($destinasi['gambar_utama']);
            }
        } else {
            $_SESSION['error_message'] = $upload_result['message'];
        }
    } else {
        $gambar_utama = $edit_mode ? $destinasi['gambar_utama'] : clean_input($_POST['gambar_utama']);
    }
    
    if ($edit_mode) {
        $query = "UPDATE destinasi SET 
                    nama = '$nama',
                    deskripsi = '$deskripsi',
                    deskripsi_lengkap = '$deskripsi_lengkap',
                    lokasi = '$lokasi',
                    gambar_utama = '$gambar_utama',
                    tiket_masuk = '$tiket_masuk',
                    fasilitas = '$fasilitas',
                    jam_buka = '$jam_buka',
                    dos = '$dos',
                    donts = '$donts',
                    maps_embed = '$maps_embed',
                    status = '$status'
                  WHERE id = $id";
        
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Destinasi berhasil diupdate!";
        log_audit('update_destinasi', "Mengubah destinasi \"$nama\".", $id, $nama);
    } else {
        $query = "INSERT INTO destinasi (nama, deskripsi, deskripsi_lengkap, lokasi, gambar_utama, tiket_masuk, fasilitas, jam_buka, dos, donts, maps_embed, status) 
                  VALUES ('$nama', '$deskripsi', '$deskripsi_lengkap', '$lokasi', '$gambar_utama', '$tiket_masuk', '$fasilitas', '$jam_buka', '$dos', '$donts', '$maps_embed', '$status')";
        
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Destinasi berhasil ditambahkan!";
        $new_id = mysqli_insert_id($conn);
        log_audit('create_destinasi', "Menambahkan destinasi \"$nama\".", $new_id, $nama);
    }
    
    redirect('destinasi.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Destinasi - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Destinasi Wisata</h1>
                <a href="destinasi.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <div class="form-container">
                <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">Nama Destinasi *</label>
                            <input type="text" id="nama" name="nama" required value="<?php echo $edit_mode ? htmlspecialchars($destinasi['nama']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="lokasi">Lokasi *</label>
                            <input type="text" id="lokasi" name="lokasi" required value="<?php echo $edit_mode ? htmlspecialchars($destinasi['lokasi']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Singkat *</label>
                        <textarea id="deskripsi" name="deskripsi" rows="2" required><?php echo $edit_mode ? htmlspecialchars($destinasi['deskripsi']) : ''; ?></textarea>
                        <small>Deskripsi yang muncul di card listing</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi_lengkap">Deskripsi Lengkap *</label>
                        <textarea id="deskripsi_lengkap" name="deskripsi_lengkap" rows="5" required><?php echo $edit_mode ? htmlspecialchars($destinasi['deskripsi_lengkap']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="gambar_utama">Gambar Utama *</label>
                        
                        <?php if ($edit_mode && !empty($destinasi['gambar_utama'])): ?>
                            <div class="current-image">
                                <img src="../<?php echo htmlspecialchars($destinasi['gambar_utama']); ?>" alt="Current Image" style="max-width: 200px; margin-bottom: 10px; border-radius: 5px;">
                                <p><small>Gambar saat ini</small></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="upload-option">
                            <label class="radio-inline">
                                <input type="radio" name="upload_type" value="file" checked> Upload File
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="upload_type" value="url"> Pakai URL
                            </label>
                        </div>
                        
                        <div id="file-upload-area">
                            <input type="file" id="gambar_file" name="gambar_file" accept="image/*">
                            <small>Upload gambar (JPG, PNG, GIF - Max 5MB)</small>
                        </div>
                        
                        <div id="url-upload-area" style="display: none;">
                            <input type="text" id="gambar_utama" name="gambar_utama" value="<?php echo $edit_mode ? htmlspecialchars($destinasi['gambar_utama']) : ''; ?>" placeholder="https://example.com/image.jpg">
                            <small>Masukkan URL gambar</small>
                        </div>
                    </div>
                    
                    <script>
                    document.querySelectorAll('input[name="upload_type"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            if (this.value === 'file') {
                                document.getElementById('file-upload-area').style.display = 'block';
                                document.getElementById('url-upload-area').style.display = 'none';
                            } else {
                                document.getElementById('file-upload-area').style.display = 'none';
                                document.getElementById('url-upload-area').style.display = 'block';
                            }
                        });
                    });
                    </script>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tiket_masuk">Tiket Masuk *</label>
                            <input type="text" id="tiket_masuk" name="tiket_masuk" required value="<?php echo $edit_mode ? htmlspecialchars($destinasi['tiket_masuk']) : ''; ?>">
                            <small>Contoh: Rp 25.000 / orang</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="jam_buka">Jam Buka *</label>
                            <input type="text" id="jam_buka" name="jam_buka" required value="<?php echo $edit_mode ? htmlspecialchars($destinasi['jam_buka']) : ''; ?>">
                            <small>Contoh: 08:00 - 17:00 WITA</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="fasilitas">Fasilitas *</label>
                        <input type="text" id="fasilitas" name="fasilitas" required value="<?php echo $edit_mode ? htmlspecialchars($destinasi['fasilitas']) : ''; ?>">
                        <small>Pisahkan dengan koma. Contoh: Toilet, Warung Makan, Parkir</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="dos">Do's (Yang Boleh Dilakukan)</label>
                        <textarea id="dos" name="dos" rows="3"><?php echo $edit_mode ? htmlspecialchars($destinasi['dos']) : ''; ?></textarea>
                        <small>Pisahkan dengan "|" (pipe). Contoh: Menjaga kebersihan|Membawa kamera|Menghormati adat</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="donts">Don'ts (Yang Tidak Boleh Dilakukan)</label>
                        <textarea id="donts" name="donts" rows="3"><?php echo $edit_mode ? htmlspecialchars($destinasi['donts']) : ''; ?></textarea>
                        <small>Pisahkan dengan "|" (pipe). Contoh: Membuang sampah|Merusak terumbu karang</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="maps_embed">Google Maps Embed URL</label>
                        <input type="text" id="maps_embed" name="maps_embed" value="<?php echo $edit_mode ? htmlspecialchars($destinasi['maps_embed']) : ''; ?>">
                        <small>URL iframe dari Google Maps (Bagian src="...")</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="aktif" <?php echo ($edit_mode && $destinasi['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($edit_mode && $destinasi['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_mode ? 'Update' : 'Simpan'; ?>
                        </button>
                        <a href="destinasi.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>