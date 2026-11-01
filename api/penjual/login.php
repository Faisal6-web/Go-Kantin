<?php
$stmt = $pdo->prepare('SELECT id_penjual, password_hash, status_akun FROM penjual WHERE email = :email');
$stmt->execute([':email' => $email]);
$penjual = $stmt->fetch();

if (!$penjual || !password_verify($password, $penjual['password_hash'])) {
    jsonResponse(401, ['message' => 'Email atau password salah']);
}
if ($penjual['status_akun'] === 'pending') {
    jsonResponse(403, ['message' => 'Akun Anda masih menunggu persetujuan admin']);
}
if ($penjual['status_akun'] === 'reject') {
    jsonResponse(403, ['message' => 'Pendaftaran akun ditolak']);
}
?>