<?php
session_start();
require '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip = $_POST['nip'];
    $password = $_POST['password'];

    // Cek pengguna
    $stmt = $pdo->prepare("SELECT id, nama, password, role FROM users WHERE nip = ?");
    $stmt->execute([$nip]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "NIP atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Instansi</title>
    <style>
        body { font-family: Arial; margin: 20px; text-align: center; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Login Instansi</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <div class="form-group">
            <label>NIP</label>
            <input type="text" name="nip" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
</body>
</html>