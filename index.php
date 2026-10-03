<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Viewport agar responsif di HP -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gerbang Perpustakaan</title>
    
    <!-- Load Library QRCode dari CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        /* --- CSS Reset & Global --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background-color: #e9ecef; /* Latar belakang untuk area desktop */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* --- Wrapper Tampilan Mobile --- */
        #app-container {
            width: 100%;
            max-width: 400px; /* Lebar maksimal seperti HP */
            min-height: 100vh; /* Full tinggi layar */
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.15); /* Bayangan jika dilihat di PC */
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Khusus jika dibuka di monitor/desktop, tinggi dibatasi agar mirip mockup HP sungguhan */
        @media screen and (min-height: 800px) {
            #app-container {
                min-height: 700px;
                border-radius: 20px;
            }
        }

        /* --- Bagian Header --- */
        header {
            background-color: #0056b3;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* --- Area Konten Utama Tengah --- */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Posisi konten selalu di tengah vertikal */
            align-items: center;     /* Posisi konten selalu di tengah horizontal */
            padding: 30px 20px;
        }

        /* --- Form Input --- */
        #form-section {
            width: 100%;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        input[type="text"] {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 8px;
            transition: 0.3s;
            letter-spacing: 2px;
        }

        input[type="text"]:focus {
            border-color: #0056b3;
            outline: none;
        }

        button.btn-primary {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #fff;
            background-color: #0056b3;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        button.btn-primary:active { background-color: #004494; }
        
        #error-msg {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 10px;
            min-height: 20px;
            font-weight: 500;
        }

        /* --- Area QR Code (Awalnya disembunyikan) --- */
        #qr-section {
            display: none;
            width: 100%;
            text-align: center;
        }

        #qr-box {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #f1f1f1;
            margin-bottom: 15px;
        }

        /* Memastikan QRCode di generate di tengah div */
        #qrcode-canvas {
            display: flex;
            justify-content: center;
        }

        #member-info {
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
        }

        button.btn-secondary {
            padding: 10px 30px;
            font-size: 1rem;
            color: #555;
            background-color: #e9ecef;
            border: 1px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Wadah Aplikasi (Mobile Size) -->
    <div id="app-container">
        <header>
            Akses Barrier Gate - Perpustakaan Pusat UIN Siber Syekh Nurjati Cirebon
        </header>
        
        <main>
            <!-- SEKSI 1: FORM INPUT -->
            <div id="form-section">
                <form id="login-form">
                    <div class="form-group">
                        <label for="nim">Masukkan NIM Anda</label>
                        <!-- Validasi HTML (min, max, required) -->
                        <input type="text" id="nim" placeholder="Contoh: 1234567890" maxlength="10" minlength="10" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn-primary" id="btn-submit">Generate QR Code</button>
                    <div id="error-msg"></div>
                </form>
            </div>

            <!-- SEKSI 2: HASIL QR CODE -->
            <div id="qr-section">
                <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">Tap QR ini ke mesin Barrier Gate</p>
                <div id="qr-box">
                    <div id="qrcode-canvas"></div>
                </div>
                <div id="member-info"></div>
                <button type="button" class="btn-secondary" onclick="resetApp()">Kembali</button>
            </div>
        </main>
    </div>

    <script>
        // --- JAVASCRIPT SPA LOGIC ---

        const loginForm = document.getElementById('login-form');
        const nimInput = document.getElementById('nim');
        const errorMsg = document.getElementById('error-msg');
        const btnSubmit = document.getElementById('btn-submit');
        
        const formSection = document.getElementById('form-section');
        const qrSection = document.getElementById('qr-section');
        const qrcodeCanvas = document.getElementById('qrcode-canvas');
        const memberInfo = document.getElementById('member-info');

        // Mencegah spasi atau karakter selain huruf/angka jika perlu (opsional, saat ini dibebaskan sesuai ketik, tapi spasi dihapus)
        nimInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, ''); 
        });

        // Event saat form disubmit
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah reload halaman
            
            const nimVal = nimInput.value.trim();
            errorMsg.innerText = ''; // Bersihkan error sebelumnya

            // 1. Validasi Sisi Klien (JavaScript)
            if (nimVal.length !== 10) {
                errorMsg.innerText = 'NIM harus tepat 10 karakter!';
                return;
            }

            // Ubah teks tombol menjadi loading
            btnSubmit.innerText = 'Memeriksa...';
            btnSubmit.disabled = true;

            // 2. Fetch ke API (memanggil endpoint 'api' tanpa .php berkat htaccess)
            fetch('api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ nim: nimVal })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Jika Sukses dari DB
                    generateQRCode(data.data.member_id, data.data.member_name);
                } else {
                    // Jika Gagal (NIM tidak ada atau pending)
                    errorMsg.innerText = data.message;
                }
            })
            .catch(error => {
                errorMsg.innerText = 'Terjadi kesalahan koneksi server.';
                console.error('Error:', error);
            })
            .finally(() => {
                // Kembalikan kondisi tombol
                btnSubmit.innerText = 'Generate QR Code';
                btnSubmit.disabled = false;
            });
        });

        // Fungsi Generate QR Code
        function generateQRCode(member_id, member_name) {
            // Sembunyikan form, tampilkan area QR
            formSection.style.display = 'none';
            qrSection.style.display = 'block';

            // Tampilkan nama member
            memberInfo.innerText = member_name;

            // Bersihkan canvas QR sebelumnya (jika ada)
            qrcodeCanvas.innerHTML = '';

            // Kombinasi format: member_id_member_name
            const qrTextContent = member_id + "_" + member_name;

            // Generate QR Code dengan ukuran kecil (150x150)
            new QRCode(qrcodeCanvas, {
                text: qrTextContent,
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }

        // Fungsi Reset Aplikasi ke Kondisi Awal
        function resetApp() {
            formSection.style.display = 'block';
            qrSection.style.display = 'none';
            nimInput.value = '';
            errorMsg.innerText = '';
            nimInput.focus();
        }
    </script>
</body>
</html>