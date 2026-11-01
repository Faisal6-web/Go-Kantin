<?php
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    jsonResponse(405, ['message' => 'Method tidak diizinkan']);
}

$idKategori = filter_var($_POST['idKategori'] ?? null, FILTER_VALIDATE_INT,
             ['options' => ['min_range' => 1]]
);
$username = trim($_POST['username'] ?? '');
$email = strtolower (trim($_POST['email'] ?? ''));
$no_hp = trim($_POST['no_hp']) ?? '';
$password = $_POST['password'] ?? '';
 
$errors = [];

if ($idKategoriPenjual === false) {
    $errors['id_kategori_penjual'] = 'Kategori penjual tidak valid';
}

if (strlen($username) > 100) {
   $errors['username'] = 'Username terlalu panjang! Maksimal 100 karakter';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Format email tidak valid';
}

if (!preg_match('/^(\+62|62|0)8[0-9]{8,12}$/', $no_Hp)) {
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

    $cek = $pdo->prepare('SELECT 1 FROM penjual WHERE email = :email');
    $cek->execute([':email' => $email]);
    if ($cek->fetch()) {
        jsonResponse(409, ['message' => 'Email sudah terdaftar']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO penjual(username, email, no_hp, password_hash, id_kategori_penjual, status_akun)
         VALUES (:username, :email, :no_hp, :password_hash, :kategori, 'pending')
         RETURNING id_penjual"
    );
    $stmt->execute([
        ':kategori'      => $idKategoriPenjual,
        ':username'      => $username,
        ':email'         => $email,
        ':no_hp'         => $noHp,
        ':password_hash' => $passwordHash,
    ]);

    $idPenjual = $stmt->fetchColumn();
    jsonResponse(201, ['message' => 'Pendaftaran diterima. Akun akan aktif setelah disetujui admin',
    'data' => ['id_penjual' => $idPenjual, 'status_akun' => 'pending'],
    ]);

    $stmt->execute([':email' => $email]);
    $penjual = $stmt->fetch();

} catch (PDOException $e) {
   if ($e->getCode() === '23505') {
        jsonResponse(409, ['message' => 'Email sudah terdaftar']);
    }
    if ($e->getCode() === '23503') {
        jsonResponse(400, ['message' => 'Kategori penjual tidak ditemukan']);
    } 
   error_log($e->getMessage());
   jsonResponse(500, ['Terjadi gangguan sistem saat ini, silakan coba beberapa saat lagi']);
}
?>