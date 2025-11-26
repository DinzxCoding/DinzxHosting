<?php
header('Content-Type: application/json');

// --- 1. KONFIGURASI SERVER GAME (SFTP) ---
$sftp_host = "sftp://nodecok.backend.biz.id"; // Ganti dengan IP VPS Game Server kamu
$sftp_port = 2023;            // Port SSH (Default 22)
$sftp_user = "dinzx.a42196cc";        // Username VPS (atau user pterodactyl/panel)
$sftp_pass = "dinzx2267"; // Password VPS

// Path file di server game.
// PENTING: SA-MP server membaca file dari folder 'scriptfiles'.
// Jika filterscriptmu membaca dari folder lain, sesuaikan path ini.
$remote_file = "/home/container/scriptfiles/users/whitelist.ini"; 

// ----------------------------------------

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);

    // Validasi input: Hanya huruf, angka, dan underscore
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response['message'] = "❌ Format nama salah! Gunakan format RP (Contoh: Nama_Marga)";
        echo json_encode($response);
        exit;
    }

    // Cek ketersediaan fungsi SSH2 di hosting website
    if (!function_exists("ssh2_connect")) {
        $response['message'] = "❌ Server Web tidak mendukung SSH2 Extension.";
        echo json_encode($response);
        exit;
    }

    // --- PROSES KONEKSI SFTP ---
    try {
        // 1. Buka Koneksi
        $connection = ssh2_connect($sftp_host, $sftp_port);
        if (!$connection) {
            throw new Exception("Gagal terhubung ke IP Server Game.");
        }

        // 2. Login
        if (!ssh2_auth_password($connection, $sftp_user, $sftp_pass)) {
            throw new Exception("Login Gagal. Cek Username/Password VPS.");
        }

        // 3. Inisialisasi SFTP
        $sftp = ssh2_sftp($connection);
        
        // Membentuk path stream untuk PHP
        // ssh2.sftp://ResourceID/path/to/file
        $stream_path = "ssh2.sftp://" . intval($sftp) . $remote_file;

        // 4. Cek apakah file ada, jika tidak, buat file baru
        if (!file_exists($stream_path)) {
            file_put_contents($stream_path, "");
        }

        // 5. Cek Duplikat (Baca isi file remote)
        $current_content = file_get_contents($stream_path);
        $lines = explode("\n", $current_content);
        
        foreach($lines as $line) {
            if (trim($line) === $username) {
                throw new Exception("⚠️ Username <b>$username</b> sudah terdaftar di whitelist!");
            }
        }

        // 6. Tulis Nama Baru (Append mode)
        $new_content = $username . "\n";
        // Menggunakan mode 'a' untuk append (menambahkan di bawah)
        $stream = fopen($stream_path, 'a');
        
        if ($stream && fwrite($stream, $new_content)) {
            fclose($stream);
            $response['success'] = true;
            $response['message'] = "✅ MISSION PASSED! <b>$username</b> berhasil ditambahkan ke Server.";
        } else {
            throw new Exception("Gagal menulis file ke folder server.");
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>