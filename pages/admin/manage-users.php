<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}
require '../../includes/config.php';

// Tambah pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $jabatan = $_POST['jabatan'];
    $pangkat = $_POST['pangkat'];
    $unit_organisasi = $_POST['unit_organisasi'];
    $seksi = $_POST['seksi'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (nama, password, nip, gender, jabatan, pangkat, unit_organisasi, seksi, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $password, $nip, $gender, $jabatan, $pangkat, $unit_organisasi, $seksi, $role]);
}

// Ambil pengguna
$stmt = $pdo->query("SELECT * FROM users ORDER BY nama");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Instansi</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Kelola Pengguna</h2>
    <p>Admin: <?php echo htmlspecialchars($_SESSION['nama']); ?> | <a href="../logout.php">Logout</a></p>
    
    <h3>Tambah Pengguna</h3>
    <form action="" method="POST">
        <input type="hidden" name="add_user" value="1">
        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="nama" required>
        </div>
        <div class="form-group">
            <label>NIP</label>
            <input type="text" name="nip" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" type="password" required>
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
        </div>
        <div class="form-group">
            <label>Jabatan</label>
            <input type="text" name="jabatan" required>
        </div>
        <div class="form-group">
            <label>Pangkat</label>
            <input type="text" name="pangkat" required>
        </div>
        <div class="form-group">
            <label>Unit Organisasi</label>
            <input type="text" name="unit_organisasi" required>
        </div>
        <div class="form-group">
            <label>Seksi</label>
            <input type="text" name="seksi" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="Admin">Admin</option>
                <option value="Pegawai">Pegawai</option>
            </select>
        </div>
        <button type="submit">Tambah</button>
    </form>

    <h3>Daftar Pengguna</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>NIP</th>
            <th>Gender</th>
            <th>Jabatan</th>
            <th>Pangkat</th>
            <th>Unit Organisasi</th>
            <th>Seksi</th>
            <th>Role</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                <td><?php echo htmlspecialchars($user['nip']); ?></td>
                <td><?php echo $user['gender'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                <td><?php echo htmlspecialchars($user['jabatan']); ?></td>
                <td><?php echo htmlspecialchars($user['pangkat']); ?></td>
                <td><?php echo htmlspecialchars($user['unit_organisasi']); ?></td>
                <td><?php echo htmlspecialchars($user['seksi']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <a href="../index.php">Kembali ke Beranda</a>
</body>
</html>