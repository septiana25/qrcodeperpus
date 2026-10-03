<?php
// Set header agar selalu mengembalikan format JSON
header('Content-Type: application/json; charset=utf-8');

// =========================================================================
// FUNGSI UNTUK MEMBACA FILE .env (Murni PHP tanpa library external)
// =========================================================================
function loadEnv($path) {
    if (!file_exists($path)) {
        return false; // File .env tidak ditemukan
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan baris komentar yang diawali dengan '#'
        if (strpos(trim($line), '#') === 0) continue;
        
        // Pecah berdasarkan tanda sama dengan '='
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Simpan ke variabel environment PHP
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Muat file .env yang ada di folder yang sama
$envLoaded = loadEnv(__DIR__ . '/.env');

if (!$envLoaded) {
    echo json_encode([
        'success' => false, 
        'message' => 'Kesalahan Sistem: File konfigurasi (.env) tidak ditemukan!'
    ]);
    exit;
}
// =========================================================================


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
    // 2. Ambil data koneksi dari file .env
    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT');
    $db_name = getenv('DB_NAME');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');

    // 3. Koneksi ke Database menggunakan PDO
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // 4. Query pencarian member (is_pending = 0)
    $sql = "SELECT member_id, member_name FROM member WHERE member_id = :nim AND is_pending = 0 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['nim' => $nim]);
    $user = $stmt->fetch();

    // 5. Hasil Query
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
}
?>