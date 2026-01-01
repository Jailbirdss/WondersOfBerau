<?php
require_once 'auth.php';
require_admin();

$edit_mode = false;
$event = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $id = intval($_GET['id']);
    $event = get_events($id);
    if (!$event)
        redirect('events.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = clean_input($_POST['nama']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $deskripsi_lengkap = clean_input($_POST['deskripsi_lengkap']);
    $tanggal = clean_input($_POST['tanggal']);
    $bulan = clean_input($_POST['bulan']);
    $hari = intval($_POST['hari']);
    $lokasi = clean_input($_POST['lokasi']);
    $jam_pelaksanaan = clean_input($_POST['jam_pelaksanaan']);
    $penyelenggara = clean_input($_POST['penyelenggara']);
    $kontak = clean_input($_POST['kontak']);
    $maps_embed = clean_input($_POST['maps_embed']);
    $tips = clean_input($_POST['tips']);
    $status = clean_input($_POST['status']);

    $gambar_utama = '';
    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] == 0) {
        $upload_result = upload_image($_FILES['gambar_file'], 'events');
        if ($upload_result['success']) {
            $gambar_utama = $upload_result['path'];
            if ($edit_mode && !empty($event['gambar_utama']) && strpos($event['gambar_utama'], 'uploads/') === 0) {
                delete_image($event['gambar_utama']);
            }
        } else {
            $_SESSION['error_message'] = $upload_result['message'];
        }
    } else {
        $gambar_utama = $edit_mode ? $event['gambar_utama'] : clean_input($_POST['gambar_utama']);
    }

    if ($edit_mode) {
        $query = "UPDATE events SET nama='$nama', deskripsi='$deskripsi', deskripsi_lengkap='$deskripsi_lengkap', 
                  tanggal='$tanggal', bulan='$bulan', hari=$hari, lokasi='$lokasi', gambar_utama='$gambar_utama',
                  jam_pelaksanaan='$jam_pelaksanaan', penyelenggara='$penyelenggara', kontak='$kontak',
                  maps_embed='$maps_embed', tips='$tips', status='$status' WHERE id = $id";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Event berhasil diupdate!";
        log_audit('update_event', "Mengubah event \"$nama\".", $id, $nama);
    } else {
        $query = "INSERT INTO events (nama, deskripsi, deskripsi_lengkap, tanggal, bulan, hari, lokasi, gambar_utama, 
                  jam_pelaksanaan, penyelenggara, kontak, maps_embed, tips, status) 
                  VALUES ('$nama', '$deskripsi', '$deskripsi_lengkap', '$tanggal', '$bulan', $hari, '$lokasi', '$gambar_utama',
                  '$jam_pelaksanaan', '$penyelenggara', '$kontak', '$maps_embed', '$tips', '$status')";
        mysqli_query($conn, $query);
        $_SESSION['success_message'] = "Event berhasil ditambahkan!";
        $new_id = mysqli_insert_id($conn);
        log_audit('create_event', "Menambahkan event \"$nama\".", $new_id, $nama);
    }
    redirect('events.php');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Event - Admin</title>
    <link rel="stylesheet" href="assets.php?file=admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Event</h1>
                <a href="events.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-container">
                <form method="POST" class="admin-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nama">Nama Event *</label>
                        <input type="text" id="nama" name="nama" required
                            value="<?php echo $edit_mode ? htmlspecialchars($event['nama']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Singkat *</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3"
                            required><?php echo $edit_mode ? htmlspecialchars($event['deskripsi']) : ''; ?></textarea>
                        <small>Deskripsi singkat yang muncul di halaman daftar event</small>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi_lengkap">Deskripsi Lengkap *</label>
                        <textarea id="deskripsi_lengkap" name="deskripsi_lengkap" rows="8"
                            required><?php echo $edit_mode ? htmlspecialchars($event['deskripsi_lengkap']) : ''; ?></textarea>
                        <small>Deskripsi detail yang muncul di halaman detail event</small>
                    </div>

                    <div class="form-group">
                        <label>Gambar Utama *</label>
                        <?php if ($edit_mode && !empty($event['gambar_utama'])): ?>
                            <div class="current-image">
                                <img src="<?php echo '../' . htmlspecialchars($event['gambar_utama']); ?>" alt="Current"
                                    style="max-width: 300px; margin-bottom: 10px;">
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
                            <input type="text" name="gambar_utama" id="gambar_utama" placeholder="Masukkan URL gambar"
                                value="<?php echo $edit_mode ? htmlspecialchars($event['gambar_utama']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal">Tanggal Lengkap *</label>
                            <input type="date" id="tanggal" name="tanggal" required
                                value="<?php echo $edit_mode ? $event['tanggal'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="bulan">Bulan (3 huruf) *</label>
                            <input type="text" id="bulan" name="bulan" required
                                value="<?php echo $edit_mode ? htmlspecialchars($event['bulan']) : ''; ?>" maxlength="3"
                                placeholder="JAN">
                            <small>Contoh: JAN, FEB, MAR, APR, dll</small>
                        </div>
                        <div class="form-group">
                            <label for="hari">Tanggal (Angka) *</label>
                            <input type="number" id="hari" name="hari" required
                                value="<?php echo $edit_mode ? $event['hari'] : ''; ?>" min="1" max="31">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lokasi">Lokasi *</label>
                        <input type="text" id="lokasi" name="lokasi" required
                            value="<?php echo $edit_mode ? htmlspecialchars($event['lokasi']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="jam_pelaksanaan">Jam Pelaksanaan *</label>
                        <input type="text" id="jam_pelaksanaan" name="jam_pelaksanaan" required
                            value="<?php echo $edit_mode ? htmlspecialchars($event['jam_pelaksanaan']) : ''; ?>"
                            placeholder="Contoh: 09:00 - 17:00 WITA">
                    </div>

                    <div class="form-group">
                        <label for="penyelenggara">Penyelenggara *</label>
                        <input type="text" id="penyelenggara" name="penyelenggara" required
                            value="<?php echo $edit_mode ? htmlspecialchars($event['penyelenggara']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="kontak">Kontak *</label>
                        <input type="text" id="kontak" name="kontak" required
                            value="<?php echo $edit_mode ? htmlspecialchars($event['kontak']) : ''; ?>"
                            placeholder="Nomor telepon atau email">
                    </div>

                    <div class="form-group">
                        <label for="tips">Tips (Pisahkan dengan | )</label>
                        <textarea id="tips" name="tips"
                            rows="5"><?php echo $edit_mode ? htmlspecialchars($event['tips']) : ''; ?></textarea>
                        <small>Contoh: Datang lebih pagi|Bawa kamera|Kenakan pakaian nyaman</small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="aktif" <?php echo ($edit_mode && $event['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="nonaktif" <?php echo ($edit_mode && $event['status'] == 'nonaktif') ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                            <?php echo $edit_mode ? 'Update' : 'Simpan'; ?></button>
                        <a href="events.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
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
</body>

</html>