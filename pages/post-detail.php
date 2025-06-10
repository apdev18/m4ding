<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require '../includes/config.php';

// Ambil artikel
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, u.nama as user_nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    header("Location: index.php");
    exit;
}

// Proses hapus postingan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    if ($post['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'Admin') {
        if ($post['image'] && file_exists("../uploads/" . $post['image'])) {
            unlink("../uploads/" . $post['image']);
        }
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        header("Location: index.php");
        exit;
    }
}

// Proses hapus komentar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($comment && ($comment['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'Admin')) {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        header("Location: post-detail.php?id=$post_id");
        exit;
    }
}

// Proses komentar atau balasan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    $comment = $_POST['comment'];
    $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;
    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, user_id, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $parent_id, $user_id, $comment]);
        header("Location: post-detail.php?id=$post_id");
        exit;
    }
}

// Proses like/unlike postingan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_action_post'])) {
    $user_id = $_SESSION['user_id'];
    $is_like = $_POST['like_action_post'] == 'like' ? 1 : 0;
    $stmt = $pdo->prepare("SELECT is_like FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $existing_like = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing_like) {
        if ($existing_like['is_like'] != $is_like) {
            $stmt = $pdo->prepare("UPDATE likes SET is_like = ?, created_at = NOW() WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$is_like, $post_id, $user_id]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id, is_like) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $is_like]);
    }
    header("Location: post-detail.php?id=$post_id");
    exit;
}

// Proses like/unlike komentar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_action_comment'])) {
    $user_id = $_SESSION['user_id'];
    $comment_id = $_POST['comment_id'];
    $is_like = $_POST['like_action_comment'] == 'like' ? 1 : 0;
    $stmt = $pdo->prepare("SELECT is_like FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
    $existing_like = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing_like) {
        if ($existing_like['is_like'] != $is_like) {
            $stmt = $pdo->prepare("UPDATE comment_likes SET is_like = ?, created_at = NOW() WHERE comment_id = ? AND user_id = ?");
            $stmt->execute([$is_like, $comment_id, $user_id]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id, is_like) VALUES (?, ?, ?)");
        $stmt->execute([$comment_id, $user_id, $is_like]);
    }
    header("Location: post-detail.php?id=$post_id");
    exit;
}

// Ambil komentar (utama)
$stmt = $pdo->prepare("SELECT c.*, u.nama as user_nama FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.parent_id IS NULL ORDER BY c.created_at DESC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil balasan untuk setiap komentar
foreach ($comments as &$comment) {
    $stmt = $pdo->prepare("SELECT c.*, u.nama as user_nama FROM comments c JOIN users u ON c.user_id = u.id WHERE c.parent_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$comment['id']]);
    $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hitung like/unlike postingan
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ? AND is_like = 1");
$stmt->execute([$post_id]);
$like_count = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ? AND is_like = 0");
$stmt->execute([$post_id]);
$unlike_count = $stmt->fetchColumn();

// Ambil daftar pengguna yang like postingan
$stmt = $pdo->prepare("SELECT u.nama FROM likes l JOIN users u ON l.user_id = u.id WHERE l.post_id = ? AND l.is_like = 1");
$stmt->execute([$post_id]);
$likers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fungsi untuk hitung like/unlike komentar
function getCommentLikes($pdo, $comment_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ? AND is_like = 1");
    $stmt->execute([$comment_id]);
    $like_count = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ? AND is_like = 0");
    $stmt->execute([$comment_id]);
    $unlike_count = $stmt->fetchColumn();
    return ['likes' => $like_count, 'unlikes' => $unlike_count];
}

// Fungsi untuk ambil daftar likers komentar
function getCommentLikers($pdo, $comment_id) {
    $stmt = $pdo->prepare("SELECT u.nama FROM comment_likes l JOIN users u ON l.user_id = u.id WHERE l.comment_id = ? AND l.is_like = 1");
    $stmt->execute([$comment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .comment { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .reply { margin-left: 20px; border-left: 2px solid #ddd; padding-left: 10px; }
        .likers { display: none; }
        .likers.show { display: block; }
    </style>
    <script>
        function toggleLikers(id) {
            document.getElementById('likers-' + id).classList.toggle('show');
        }
        function confirmDelete() {
            return confirm('Yakin ingin hapus?');
        }
    </script>
</head>
<body>
    <h1><?php echo htmlspecialchars($post['title']); ?></h1>
    <p><small>Diposting oleh: <?php echo htmlspecialchars($post['user_nama']); ?> | <?php echo $post['created_at']; ?> | Kategori: <?php echo htmlspecialchars($post['category']); ?></small></p>
    <?php if ($post['image']): ?>
        <p><img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Gambar" style="max-width: 100%;"></p>
    <?php endif; ?>
    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
    
    <?php if ($post['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'Admin'): ?>
        <p>
            <a href="edit-post.php?id=<?php echo $post_id; ?>">Edit</a> | 
            <form action="" method="POST" style="display: inline;">
                <input type="hidden" name="delete_post" value="1">
                <button type="submit" onclick="return confirmDelete()">Hapus</button>
            </form>
        </p>
    <?php endif; ?>
    
    <p>
        <a href="#" onclick="toggleLikers('post')">Like: <?php echo $like_count; ?></a> | Unlike: <?php echo $unlike_count; ?>
    </p>
    <div id="likers-post" class="likers">
        <h4>Yang Menyukai Postingan:</h4>
        <?php if ($likers): ?>
            <ul>
                <?php foreach ($likers as $liker): ?>
                    <li><?php echo htmlspecialchars($liker['nama']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Belum ada yang menyukai.</p>
        <?php endif; ?>
    </div>
    
    <form action="" method="POST">
        <input type="hidden" name="like_action_post" value="like">
        <button type="submit">Like</button>
    </form>
    <form action="" method="POST">
        <input type="hidden" name="like_action_post" value="unlike">
        <button type="submit">Unlike</button>
    </form>

    <h3>Komentar</h3>
    <form action="" method="POST">
        <div>
            <label>Komentar</label>
            <textarea name="comment" rows="3" required></textarea>
        </div>
        <button type="submit">Kirim</button>
    </form>
    
    <?php foreach ($comments as $comment): ?>
        <div class="comment">
            <strong><?php echo htmlspecialchars($comment['user_nama']); ?></strong>
            <p><?php echo htmlspecialchars($comment['comment']); ?></p>
            <small><?php echo $comment['created_at']; ?></small>
            <?php
                $comment_stats = getCommentLikes($pdo, $comment['id']);
            ?>
            <p>
                <a href="#" onclick="toggleLikers('comment-<?php echo $comment['id']; ?>')">Like: <?php echo $comment_stats['likes']; ?></a> | Unlike: <?php echo $comment_stats['unlikes']; ?>
            </p>
            <div id="likers-comment-<?php echo $comment['id']; ?>" class="likers">
                <h4>Yang Menyukai Komentar:</h4>
                <?php $comment_likers = getCommentLikers($pdo, $comment['id']);
                if ($comment_likers): ?>
                    <ul>
                        <?php foreach ($comment_likers as $liker): ?>
                            <li><?php echo htmlspecialchars($liker['nama']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Belum ada yang menyukai.</p>
                <?php endif; ?>
            </div>
            
            <form action="" method="POST">
                <input type="hidden" name="like_action_comment" value="like">
                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                <button type="submit">Like</button>
            </form>
            <form action="" method="POST">
                <input type="hidden" name="like_action_comment" value="unlike">
                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                <button type="submit">Unlike</button>
            </form>
            
            <?php if ($comment['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'Admin'): ?>
                <p>
                    <a href="edit-comment.php?id=<?php echo $comment['id']; ?>&amp;post_id=<?php echo $post_id; ?>">Edit</a> | 
                    <form action="" method="POST" style="display: inline;">
                        <input type="hidden" name="delete_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <button type="submit" onclick="return confirmDelete()">Hapus</button>
                    </form>
                </p>
            <?php endif; ?>
            
            <!-- Form balasan -->
            <form action="" method="POST">
                <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                <div>
                    <label>Balas Komentar</label>
                    <textarea name="comment" rows="2" required></textarea>
                </div>
                <button type="submit">Balas</button>
            </form>
            
            <!-- Tampilkan balasan -->
            <?php foreach ($comment['replies'] as $reply): ?>
                <div class="reply">
                    <strong><?php echo htmlspecialchars($reply['user_nama']); ?></strong>
                    <p><?php echo htmlspecialchars($reply['comment']); ?></p>
                    <small><?php echo $reply['created_at']; ?></small>
                    <?php
                        $reply_stats = getCommentLikes($pdo, $reply['id']);
                    ?>
                    <p>
                        <a href="#" onclick="toggleLikers('comment-<?php echo $reply['id']; ?>')">Like: <?php echo $reply_stats['likes']; ?></a> | Unlike: <?php echo $reply_stats['unlikes']; ?>
                    </p>
                    <div id="likers-comment-<?php echo $reply['id']; ?>" class="likers">
                        <h4>Yang Menyukai Balasan:</h4>
                        <?php $reply_likers = getCommentLikers($pdo, $reply['id']);
                        if ($reply_likers): ?>
                            <ul>
                                <?php foreach ($reply_likers as $liker): ?>
                                    <li><?php echo htmlspecialchars($liker['nama']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>Belum ada yang menyukai.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="like_action_comment" value="like">
                        <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                        <button type="submit">Like</button>
                    </form>
                    <form action="" method="POST">
                        <input type="hidden" name="like_action_comment" value="unlike">
                        <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                        <button type="submit">Unlike</button>
                    </form>
                    
                    <?php if ($reply['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'Admin'): ?>
                        <p>
                            <a href="edit-comment.php?id=<?php echo $reply['id']; ?>&amp;post_id=<?php echo $post_id; ?>">Edit</a> | 
                            <form action="" method="POST" style="display: inline;">
                                <input type="hidden" name="delete_comment" value="1">
                                <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                <button type="submit" onclick="return confirmDelete()">Hapus</button>
                            </form>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
    <a href="index.php">Kembali</a>
</body>
</html>