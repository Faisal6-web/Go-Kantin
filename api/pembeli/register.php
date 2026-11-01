<?php
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    jsonResponse(405, ['message' => 'Method tidak diizinkan']);
}

$username = trim($_POST['username'] ?? '');
$email = strtolower (trim($_POST['email'] ?? ''));
$no_hp = trim($_POST['no_hp']) ?? '';
$password = $_POST['password'] ?? '';
 
$errors = [];
if (strlen($username) > 100) {
   $errors['username'] = 'Username terlalu panjang! Maksimal 100 karakter';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Format email tidak valid';
}

if (!preg_match('/^(\+62|62|0)8[0-9]{8,12}$/', $noHp)) {
    $errors['no_hp'] = 'Nomor HP tidak valid';
}

if(strlen($password) < 8) {
    $errors['password'] = 'Gagal, Password minimal 8 karakter';
}

if ($errors) {
    jsonResponse(400, ['message' => 'Validasi Gagal', 'errors' => $errors]);
}

try {
    $pdo = getConnection();

    $cek = $pdo->prepare('SELECT 1 FROM pembeli WHERE email = :email');
    $cek->execute([':email' => $email]);

    if ($cek->fetch()) {
        jsonResponse(409, ['message' => 'Email sudah terdaftar']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO pembeli(username, email, no_hp, password_hash)
         VALUES (:username, :email, :no_hp, :password_hash)
         RETURNING id_pembeli'
    );
    $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':no_hp'         => $noHp,
        ':password_hash' => $passwordHash,
    ]);
    $idPembeli = $stmt->fetchColumn();

    jsonResponse(201, ['message' => 'Registrasi berhasil']);

} catch (PDOException $e) {
   error_log($e->getMessage());
   jsonResponse(500, ['message' => 'Terjadi gangguan sistem saat ini, silakan coba beberapa saat lagi.']);
}
?>