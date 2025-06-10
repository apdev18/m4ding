<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../includes/config.php';

// Ambil komentar
if (!isset($_GET['id']) || !isset($_GET['post_id'])) {
    header("Location: index.php");
    exit;
}
$comment_id = $_GET['id'];
$post_id = $_GET['post_id'];
$stmt = $pdo->prepare("SELECT c.*, p.id as post_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$comment || $comment['post_id'] != $post_id) {
    header("Location: index.php");
    exit;
}

// Cek akses: Hanya pengomentar atau Admin
if ($comment['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit;
}

// Proses edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = $_POST['comment'];
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("UPDATE comments SET comment = ? WHERE id = ?");
        $stmt->execute([$comment_text, $comment_id]);
        header("Location: post-detail.php?id=$post_id");
        exit;
    } else {
        $error = "Komentar tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Komentar</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Edit Komentar</h2>
    <p>Pegawai: <?php echo htmlspecialchars($_SESSION['nama']); ?></p>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <div class="form-group">
            <label>Komentar</label>
            <textarea name="comment" rows="3" required><?php echo htmlspecialchars($comment['comment']); ?></textarea>
        </div>
        <button type="submit">Simpan Perubahan</button>
    </form>
    <a href="post-detail.php?id=<?php echo $post_id; ?>">Kembali</a>
</body>
</html>