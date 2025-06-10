<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../includes/config.php';

// Ambil postingan
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    header("Location: index.php");
    exit;
}

// Cek akses: Hanya pemilik atau Admin
if ($post['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit;
}

// Proses edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category'];
    $image = $post['image'];

    // Validasi input
    if (empty($title) || empty($content) || empty($category)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Proses upload gambar baru
        if ($_FILES['image']['name']) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 2000000) {
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/$image");
                // Hapus gambar lama jika ada
                if ($post['image'] && file_exists("../uploads/" . $post['image'])) {
                    unlink("../uploads/" . $post['image']);
                }
            } else {
                $error = "Gambar tidak valid (format JPG/PNG, max 2MB).";
            }
        }

        // Update database
        if (!isset($error)) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, image = ?, category = ? WHERE id = ?");
            $stmt->execute([$title, $content, $image, $category, $post_id]);
            header("Location: post-detail.php?id=$post_id");
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
    <title>Edit Postingan</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Edit Postingan</h2>
    <p>Pegawai: <?php echo htmlspecialchars($_SESSION['nama']); ?></p>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Judul</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>
        <div class="form-group">
            <label>Isi</label>
            <textarea name="content" rows="5" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Gambar (JPG/PNG, max 2MB)</label>
            <input type="file" name="image" accept="image/jpeg,image/png">
            <?php if ($post['image']): ?>
                <p>Gambar saat ini: <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Gambar" style="max-width: 100px;"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <select name="category" required>
                <option value="Kegiatan" <?php echo $post['category'] == 'Kegiatan' ? 'selected' : ''; ?>>Kegiatan</option>
                <option value="Pengumuman" <?php echo $post['category'] == 'Pengumuman' ? 'selected' : ''; ?>>Pengumuman</option>
            </select>
        </div>
        <button type="submit">Simpan Perubahan</button>
    </form>
    <a href="post-detail.php?id=<?php echo $post_id; ?>">Kembali</a>
</body>
</html>