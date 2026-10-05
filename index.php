<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Viewport agar responsif di HP -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Meta tag untuk memaksa light mode pada browser yang mendukung color-scheme -->
    <meta name="color-scheme" content="light">
    <title>Gerbang Perpustakaan</title>
    
    <!-- Load Library QRCode dari CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        /* --- CSS Reset & Global --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            transition: background-color 0.3s;
        }

        /* --- Wrapper Tampilan Mobile --- */
        #app-container {
            width: 100%;
            max-width: 400px;
            min-height: 100vh;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: all 0.3s;
        }

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
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        /* --- Form Input --- */
        #form-section { width: 100%; text-align: center; }
        .form-group { margin-bottom: 20px; width: 100%; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        
        input[type="text"] {
            width: 100%; padding: 15px; font-size: 1.1rem; text-align: center;
            border: 2px solid #ccc; border-radius: 8px; transition: 0.3s; letter-spacing: 2px;
        }
        input[type="text"]:focus { border-color: #0056b3; outline: none; }
        
        button.btn-primary {
            width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold; color: #fff;
            background-color: #0056b3; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s;
        }
        button.btn-primary:active { background-color: #004494; }
        #error-msg { color: #dc3545; font-size: 0.9rem; margin-top: 10px; min-height: 20px; font-weight: 500; }

        /* --- Area QR Code --- */
        #qr-section { display: none; width: 100%; text-align: center; }
        #qr-box {
            background-color: #ffffff; padding: 20px; border-radius: 15px;
            display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #f1f1f1; margin-bottom: 15px;
        }
        #qrcode-canvas { display: flex; justify-content: center; }
        #member-info { margin-top: 10px; margin-bottom: 20px; font-size: 1.1rem; color: #333; font-weight: 600; }
        
        button.btn-secondary {
            padding: 10px 30px; font-size: 1rem; color: #555; background-color: #e9ecef;
            border: 1px solid #ccc; border-radius: 8px; cursor: pointer;
        }

        /* =========================================================
           MODE QR ACTIVE: Paksa Putih Bersih (Override Dark Mode)
           ========================================================= */
        body.qr-active {
            background-color: #ffffff !important;
        }
        #app-container.qr-active {
            max-width: 100% !important; /* Penuhi layar saat QR muncul */
            background-color: #ffffff !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        body.qr-active header {
            display: none !important; /* Sembunyikan header biru */
        }
        body.qr-active #qr-box {
            box-shadow: none !important; /* Hilangkan shadow agar benar-benar rata putih */
            border-color: #ffffff !important;
        }
        /* Pastikan teks tetap hitam tegas meski HP di set ke Dark Mode */
        body.qr-active p, body.qr-active #member-info {
            color: #000000 !important;
        }
    </style>
</head>
<body>

    <div id="app-container">
        <header>
            Akses Barrier Gate - Perpustakaan Pusat UIN Siber Syekh Nurjati Cirebon
        </header>
        
        <main>
            <div id="form-section">
                <form id="login-form">
                    <div class="form-group">
                        <label for="nim">Masukkan NIM Anda</label>
                        <input type="text" id="nim" placeholder="Contoh: 1234567890" maxlength="12" minlength="8" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn-primary" id="btn-submit">Generate QR Code</button>
                    <div id="error-msg"></div>
                </form>
            </div>

            <div id="qr-section">
                <p style="margin-bottom: 15px; font-size: 0.9rem; font-weight:bold;">Tap QR ini ke mesin Barrier Gate</p>
                <div id="qr-box">
                    <div id="qrcode-canvas"></div>
                </div>
                <div id="member-info"></div>
                <button type="button" class="btn-secondary" onclick="resetApp()">Kembali</button>
            </div>
        </main>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
        const nimInput = document.getElementById('nim');
        const errorMsg = document.getElementById('error-msg');
        const btnSubmit = document.getElementById('btn-submit');
        
        const formSection = document.getElementById('form-section');
        const qrSection = document.getElementById('qr-section');
        const qrcodeCanvas = document.getElementById('qrcode-canvas');
        const memberInfo = document.getElementById('member-info');

        // --- Variabel untuk Wake Lock ---
        let wakeLock = null;

        // Fungsi untuk meminta layar tetap menyala
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                    console.log('Wake Lock aktif: Layar tidak akan mati.');
                }
            } catch (err) {
                console.error(`Wake Lock error: ${err.name}, ${err.message}`);
            }
        }

        // Fungsi untuk melepaskan Wake Lock
        function releaseWakeLock() {
            if (wakeLock !== null) {
                wakeLock.release().then(() => {
                    wakeLock = null;
                    console.log('Wake Lock dilepas: Layar bisa mati otomatis.');
                });
            }
        }

        // Jika user pindah tab/aplikasi dan kembali, minta ulang Wake Lock
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') {
                requestWakeLock();
            }
        });

        nimInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, ''); 
        });

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const nimVal = nimInput.value.trim();
            errorMsg.innerText = '';


            if (nimVal.length < 8 || nimVal.length > 12) {
                errorMsg.innerText = 'NIM  8 karakter maksimal 12 karakter!';
                return;
            }

            btnSubmit.innerText = 'Memeriksa...';
            btnSubmit.disabled = true;

            fetch('api', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nim: nimVal })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    generateQRCode(data.data.nim, data.data.nama);
                } else {
                    errorMsg.innerText = data.message;
                }
            })
            .catch(error => {
                errorMsg.innerText = 'Terjadi kesalahan koneksi server.';
            })
            .finally(() => {
                btnSubmit.innerText = 'Generate QR Code';
                btnSubmit.disabled = false;
            });
        });

        function generateQRCode(nim, nama) {
            // 1. Sembunyikan form, tampilkan area QR
            formSection.style.display = 'none';
            qrSection.style.display = 'block';
            console.log(`QR Code untuk NIM: ${nim}, Nama: ${nama} berhasil dibuat.`);
            

            // 2. Aktifkan Mode Putih Bersih (Override)
            document.body.classList.add('qr-active');
            document.getElementById('app-container').classList.add('qr-active');

            // 3. Aktifkan Fitur Layar Tidak Mati
            requestWakeLock();

            memberInfo.innerText = nama;
            qrcodeCanvas.innerHTML = '';

            const qrTextContent = nim + "_" + nama;

            new QRCode(qrcodeCanvas, {
                text: qrTextContent,
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }

        function resetApp() {
            // 1. Kembalikan ke tampilan normal (matikan mode putih bersih)
            document.body.classList.remove('qr-active');
            document.getElementById('app-container').classList.remove('qr-active');
            
            // 2. Lepaskan Wake Lock (biarkan layar bisa mati otomatis lagi)
            releaseWakeLock();

            // 3. Reset UI form
            formSection.style.display = 'block';
            qrSection.style.display = 'none';
            nimInput.value = '';
            errorMsg.innerText = '';
            nimInput.focus();
        }
    </script>
</body>
</html>