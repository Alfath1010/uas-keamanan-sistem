<?php $__env->startSection('title', 'Kunci'); ?>

<?php $__env->startSection('content'); ?>
    <h1>Manajemen Kunci</h1>

    <h2>Status Anda saat ini</h2>
    <p id="current-status" class="status-message pending">Memeriksa...</p>

    <h2>Buat pasangan kunci baru</h2>
    <p class="hint">Pasangan kunci ECC (X25519) + Schnorr. Segera unduh setelah membuat, karena kunci ini akan hilang apabila Anda berpindah halaman.</p>

    <button id="generate-btn">Buat Pasangan Kunci Baru</button>
    <p id="generate-message" class="status-message"></p>

    <div id="download-section" style="display:none;">
        <h2>Unduh kunci privat Anda sekarang</h2>
        <p><strong>Kunci ini hanya ditampilkan sekali. Simpan di tempat yang aman sebelum melanjutkan.</strong></p>
        <button id="download-ecprv" class="secondary">Unduh kunci privat ECC (.ecprv)</button>
        <button id="download-scprv" class="secondary">Unduh kunci privat Schnorr (.scprv)</button>
        <p id="download-status" class="hint"></p>

        <h2>Unggah kunci publik ke server</h2>
        <p>Ini membuat Anda dapat dihubungi. Pengguna lain memerlukan kunci ini untuk mengirim pesan terenkripsi yang dapat diverifikasi kepada Anda.</p>
        <button id="upload-btn" disabled>Unggah Kunci Publik</button>
        <p id="upload-message" class="status-message"></p>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script type="module">
    let generatedKeys = null; // { ecc: {publicKey, privateKey}, schnorr: {publicKey, privateKey} }
    let downloadedEcc = false;
    let downloadedSchnorr = false;

    function downloadTextFile(filename, content) {
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    function updateDownloadStatus() {
        const statusEl = document.getElementById('download-status');
        const uploadBtn = document.getElementById('upload-btn');

        if (downloadedEcc && downloadedSchnorr) {
            statusEl.textContent = 'Kedua kunci telah diunduh. Harap mengunggah kunci publik sekarang.';
            uploadBtn.disabled = false;
        } else {
            statusEl.textContent = 'Unduh kedua berkas kunci privat sebelum mengunggah kunci publik Anda.';
            uploadBtn.disabled = true;
        }
    }

    async function checkCurrentStatus() {
        const statusEl = document.getElementById('current-status');
        const selfUuid = Api.getSelfUuid();

        if (!selfUuid) {
            statusEl.className = 'status-message error';
            statusEl.textContent = 'Tidak dapat menemukan akun Anda. Harap keluar lalu masuk kembali.';
            return;
        }

        try {
            const result = await Api.request('GET', `/users/${selfUuid}/keys`);
            if (result.data.ecc_public_key && result.data.schnorr_public_key) {
                statusEl.className = 'status-message success';
                statusEl.textContent = 'Kunci publik Anda sudah terunggah ke server. Pengguna lain sudah dapat mengirim pesan kepada Anda.';
            } else {
                statusEl.className = 'status-message error';
                statusEl.textContent = 'Anda belum mengunggah kunci publik ke server. Buat pasangan kunci di bawah agar dapat dihubungi.';
            }
        } catch (err) {
            statusEl.className = 'status-message error';
            statusEl.textContent = `Tidak dapat memeriksa status kunci (${err.errorCode || err.statusCode}): ${err.message}`;
        }
    }

    document.getElementById('generate-btn').addEventListener('click', async () => {
        const messageEl = document.getElementById('generate-message');
        const generateBtn = document.getElementById('generate-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Membuat...';
        generateBtn.disabled = true;

        try {
            await SecureMessaging.ECC.ready();
            const ecc = SecureMessaging.ECC.generateKeyPair();

            const paramsResponse = await Api.request('GET', '/schnorr/parameters');
            const schnorr = SecureMessaging.Schnorr.generateKeyPair(paramsResponse.data);

            generatedKeys = { ecc, schnorr };
            downloadedEcc = false;
            downloadedSchnorr = false;
            document.getElementById('download-section').style.display = 'block';
            updateDownloadStatus();
            messageEl.className = 'status-message success';
            messageEl.textContent = 'Pasangan kunci berhasil dibuat. Unduh kunci privat Anda di bawah sebelum berpindah halaman.';
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
        } finally {
            generateBtn.disabled = false;
        }
    });

    document.getElementById('download-ecprv').addEventListener('click', () => {
        downloadTextFile('key.ecprv', generatedKeys.ecc.privateKey);
        downloadedEcc = true;
        updateDownloadStatus();
    });

    document.getElementById('download-scprv').addEventListener('click', () => {
        downloadTextFile('key.scprv', generatedKeys.schnorr.privateKey);
        downloadedSchnorr = true;
        updateDownloadStatus();
    });

    document.getElementById('upload-btn').addEventListener('click', async () => {
        const messageEl = document.getElementById('upload-message');
        const uploadBtn = document.getElementById('upload-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Mengunggah...';
        uploadBtn.disabled = true;

        try {
            await Api.request('POST', '/users/keys', {
                ecc_public_key: generatedKeys.ecc.publicKey,
                schnorr_public_key: generatedKeys.schnorr.publicKey,
            });
            messageEl.className = 'status-message success';
            messageEl.textContent = 'Kunci publik berhasil diunggah.';
            checkCurrentStatus();
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
            uploadBtn.disabled = false;
        }
    });

    checkCurrentStatus();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project\resources\views/keys/index.blade.php ENDPATH**/ ?>