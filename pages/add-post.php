<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    $image = '';

    // Validasi input
    if (empty($title) || empty($content) || empty($category)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Proses upload gambar
        if ($_FILES['image']['name']) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 2000000) {
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/$image");
            } else {
                $error = "Gambar tidak valid (format JPG/PNG, max 2MB).";
            }
        }

        // Simpan ke database
        if (!isset($error)) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $image, $category]);
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Postingan</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Tambah Postingan</h2>
    <p>Pegawai: <?php echo htmlspecialchars($_SESSION['nama']); ?></p>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Judul</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Isi</label>
            <textarea name="content" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <label>Gambar (JPG/PNG, max 2MB)</label>
            <input type="file" name="image" accept="image/jpeg,image/png">
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <select name="category" required>
                <option value="Kegiatan">Kegiatan</option>
                <option value="Pengumuman">Pengumuman</option>
            </select>
        </div>
        <button type="submit">Simpan</button>
    </form>
    <a href="index.php">Kembali</a>
</body>
</html>