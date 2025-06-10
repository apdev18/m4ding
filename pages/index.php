<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../includes/config.php';
$stmt = $pdo->query("SELECT p.*, u.nama as user_nama FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 6");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instansi</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .post { border-bottom: 1px solid #ddd; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Instansi: Mading Digital</h1>
    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?> | <a href="logout.php">Logout</a></p>
    <h2>Kegiatan Pegawai</h2>
    <?php foreach ($posts as $post): ?>
        <div class="post">
            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
            <p><?php echo substr($post['content'], 0, 100) . '...'; ?></p>
            <p><small>Diposting oleh: <?php echo htmlspecialchars($post['user_nama']); ?> | <?php echo $post['created_at']; ?> | <?php echo $post['category']; ?></small></p>
            <a href="post-detail.php?id=<?php echo $post['id']; ?>">Baca Selengkapnya</a>
        </div>
    <?php endforeach; ?>
    <a href="add-post.php">Tambah Postingan</a>
</body>
</html>