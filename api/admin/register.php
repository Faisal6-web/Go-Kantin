<?php
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    jsonResponse(405, ['message' => 'Method tidak diizinkan']);
}

$username = trim($_POST['username'] ?? '');
$email = strtolower (trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
 
$errors = [];
if (strlen($username) > 100) {
   $errors['username'] = 'Username terlalu panjang! Maksimal 100 karakter';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Format email tidak valid';
}

if(strlen($password) < 8) {
    $errors['password'] = 'Gagal, Password minimal 8 karakter';
}

if ($errors) {
    jsonResponse(400, ['message' => 'Validasi Gagal', 'errors' => $errors]);
}

try {
    $pdo = getConnection();

    $cek = $pdo->prepare('SELECT 1 FROM admin WHERE email = :email');
    $cek->execute([':email' => $email]);

    if ($cek->fetch()) {
        jsonResponse(409, ['message' => 'Email sudah terdaftar']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO admin(username, email, password_hash)
         VALUES (:username, :email, :password_hash)
         RETURNING id_admin'
    );
    $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
    ]);
    $idAdmin = $stmt->fetchColumn();

   jsonResponse(201, ['message' => 'Registrasi berhasil']);

} catch (PDOException $e) {
   error_log($e->getMessage());
   jsonResponse(500, ['Terjadi gangguan sistem saat ini, silakan coba beberapa saat lagi']);
}
?>