<?php
// Set header agar selalu mengembalikan format JSON
header('Content-Type: application/json; charset=utf-8');

// Konfigurasi Database (Sesuai permintaan)
$db_host = '192.168.36.36';
$db_port = '3306';
$db_name = 'perpuspusatdb';
$db_user = 'sibasis_user';
$db_pass = 'SIBASIS.Sejati99';

// Tangkap request JSON dari Frontend (JavaScript Fetch API)
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);
$nim = isset($data['nim']) ? trim($data['nim']) : '';

// 1. Validasi Sisi Server (PHP): Pastikan tepat 10 karakter
if (strlen($nim) !== 10) {
    echo json_encode([
        'success' => false, 
        'message' => 'Validasi Server Gagal: NIM harus 10 karakter!'
    ]);
    exit;
}

try {
    // 2. Koneksi ke Database menggunakan PDO
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // 3. Query pencarian member (is_pending = 0)
    $sql = "SELECT member_id, member_name FROM member WHERE member_id = :nim AND is_pending = 0 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['nim' => $nim]);
    $user = $stmt->fetch();

    // 4. Hasil Query
    if ($user) {
        // Jika ditemukan dan tidak pending
        echo json_encode([
            'success' => true,
            'data' => [
                'nim' => $user['member_id'],
                'nama' => $user['member_name']
            ]
        ]);
    } else {
        // Jika tidak ada atau masih pending
        echo json_encode([
            'success' => false,
            'message' => 'Akses Ditolak: NIM tidak ditemukan atau status tidak aktif.'
        ]);
    }

} catch (PDOException $e) {
    // Menangkap error koneksi database
    echo json_encode([
        'success' => false,
        'message' => 'Sistem Sibuk / Gagal terhubung ke Database.'
    ]);
    // Catatan: Hapus / sembunyikan $e->getMessage() saat di production agar aman
}
?>