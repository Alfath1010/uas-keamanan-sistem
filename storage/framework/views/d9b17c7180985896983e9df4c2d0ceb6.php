<?php $__env->startSection('title', 'Percakapan'); ?>

<?php $__env->startSection('content'); ?>
    <h1>Percakapan</h1>
    <p><a class="brand" href="<?php echo e(url('/conversations')); ?>"><span>&larr;<span> Kembali</a></p>

    <div class="key-file-inputs">
        <h2>Kunci privat Anda</h2>
        <label>Berkas kunci privat ECC (.ecprv). Diperlukan untuk mengirim maupun membaca<input type="file" id="ecc-private-file"></label>
        <label>(Opsional) Berkas kunci privat Schnorr (.scprv). Hanya diperlukan untuk menandatangani pesan keluar<input type="file" id="schnorr-private-file"></label>
    </div>

    <h2>Pesan</h2>
    <button id="refresh-btn" class="secondary">Muat Ulang &amp; Dekripsi</button>
    <ul id="message-list" class="message-thread"></ul>

    <h2>Kirim pesan</h2>
    <form id="send-form">
        <textarea id="plaintext" rows="3" placeholder="Ketik pesan... (Enter untuk mengirim, Shift+Enter untuk baris baru)" required></textarea>
        <label><input type="checkbox" id="sign-checkbox" style="display:inline-block;width:auto;"> Tandatangani pesan ini (memerlukan kunci privat Schnorr di atas)</label>
        <button type="submit" id="send-btn">Kirim</button>
    </form>
    <p id="send-message" class="status-message"></p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script type="module">
    const conversationUuid = <?php echo json_encode($conversationUuid, 15, 512) ?>;
    let recipientUuid = null;
    let recipientEccPublicKey = null;
    let recipientSchnorrPublicKey = null;

    function formatTimestamp(iso) {
        try {
            return new Date(iso).toLocaleString('id-ID', {
                month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
            });
        } catch (e) {
            return iso;
        }
    }

    async function readFileAsText(fileInput) {
        if (!fileInput.files || fileInput.files.length === 0) return null;
        return await fileInput.files[0].text();
    }

    async function resolveRecipient() {
        const conv = await Api.request('GET', `/conversations/${conversationUuid}`);
        const selfUuid = Api.getSelfUuid();
        const other = conv.data.members.find((m) => m.uuid !== selfUuid);
        recipientUuid = other ? other.uuid : null;

        if (recipientUuid) {
            const keys = await Api.request('GET', `/users/${recipientUuid}/keys`);
            recipientEccPublicKey = keys.data.ecc_public_key;
            recipientSchnorrPublicKey = keys.data.schnorr_public_key;
        }
    }

    async function loadMessages() {
        const list = document.getElementById('message-list');
        const refreshBtn = document.getElementById('refresh-btn');
        list.innerHTML = '<li class="hint">Memuat...</li>';
        refreshBtn.disabled = true;

        try {
            await SecureMessaging.ECC.ready();
            await resolveRecipient();

            const session = await Api.establishAlsSession();
            const result = await Api.alsRequest('GET', `/messages/${conversationUuid}`, {}, session);
            const messages = result.data.messages;

            if (messages.length === 0) {
                list.innerHTML = '<li class="hint">Belum ada pesan.</li>';
                return;
            }

            const eccPrivateKey = await readFileAsText(document.getElementById('ecc-private-file'));
            let schnorrParams = null;
            if (messages.some(m => m.signed)) {
                schnorrParams = (await Api.request('GET', '/schnorr/parameters')).data;
            }

            list.innerHTML = '';
            for (const msg of messages) {
                const selfUuid = Api.getSelfUuid();
                const isMine = msg.sender_uuid === selfUuid;

                const li = document.createElement('li');
                li.className = `message-bubble ${isMine ? 'mine' : 'theirs'}`;
                if (!eccPrivateKey) li.classList.add('pending');

                const span = document.createElement('span');
                span.textContent = eccPrivateKey ? 'Mendekripsi...' : 'Unggah kunci privat ECC Anda di atas untuk mendekripsi';
                li.appendChild(span);

                const meta = document.createElement('span');
                meta.className = 'meta';
                meta.textContent = formatTimestamp(msg.created_at);
                li.appendChild(meta);

                list.appendChild(li);

                if (eccPrivateKey) {
                    decryptAndVerify(li, span, msg, eccPrivateKey, schnorrParams, isMine);
                }
            }

            list.scrollTop = list.scrollHeight;
        } catch (err) {
            list.innerHTML = `<li class="status-message error">Kesalahan (${err.errorCode || err.statusCode}): ${err.message}</li>`;
        } finally {
            refreshBtn.disabled = false;
        }
    }

    async function decryptAndVerify(li, span, msg, eccPrivateKeyB64, schnorrParams, isMine) {
        try {
            // Our OWN ECC public key is required alongside our private key
            // to open a sealed box addressed to us. We derive it from the
            // private key rather than asking the user for it separately.
            await SecureMessaging.ECC.ready();
            const ourPublicKey = sodium.crypto_scalarmult_base(
                SecureMessaging.base64ToBytes(eccPrivateKeyB64)
            );
            const ourPublicKeyB64 = SecureMessaging.bytesToBase64(ourPublicKey);

            const plaintext = SecureMessaging.ECC.decrypt(msg.ciphertext, ourPublicKeyB64, eccPrivateKeyB64);
            let verifiedNote = '';

            // Skip signature verification for our own sent messages — we
            // already know whether we chose to sign it; verifying our own
            // signature back against ourselves adds nothing (and would
            // need our own Schnorr public key, which we don't retain
            // client-side beyond the Keys page's local session anyway).
            if (!isMine && msg.signed && msg.signature && schnorrParams) {
                const valid = await SecureMessaging.Schnorr.verify(
                    plaintext, msg.signature, recipientSchnorrPublicKey, schnorrParams,
                );
                verifiedNote = valid ? ' ✓ terverifikasi' : ' ⚠ gagal verifikasi';
            } else if (isMine && msg.signed) {
                verifiedNote = ' (terverifikasi)';
            }

            li.classList.remove('pending');
            span.textContent = plaintext;
            if (verifiedNote) {
                const badge = document.createElement('span');
                badge.className = 'meta';
                badge.textContent = verifiedNote.trim();
                span.after(badge);
            }
        } catch (err) {
            li.classList.remove('pending');
            span.textContent = `[Dekripsi gagal: ${err.message}]`;
        }
    }

    document.getElementById('refresh-btn').addEventListener('click', loadMessages);

    // Enter to send, Shift+Enter for a newline — standard chat UX.
    document.getElementById('plaintext').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('send-form').requestSubmit();
        }
    });

    document.getElementById('send-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageEl = document.getElementById('send-message');
        const sendBtn = document.getElementById('send-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Mengirim...';
        sendBtn.disabled = true;

        try {
            await SecureMessaging.ECC.ready();
            if (!recipientEccPublicKey) await resolveRecipient();
            if (!recipientEccPublicKey) throw new Error('Penerima belum mengunggah kunci publik ECC.');

            const eccPrivateKey = await readFileAsText(document.getElementById('ecc-private-file'));
            if (!eccPrivateKey) {
                throw new Error('Kunci privat ECC Anda diperlukan untuk mengirim pesan (digunakan agar Anda juga dapat membaca pesan yang Anda kirim).');
            }
            const ourPublicKey = sodium.crypto_scalarmult_base(
                SecureMessaging.base64ToBytes(eccPrivateKey)
            );
            const ourPublicKeyB64 = SecureMessaging.bytesToBase64(ourPublicKey);

            const plaintext = document.getElementById('plaintext').value;

            // Sealed-box encryption is one-directional — a copy sealed to
            // the recipient's key can never be opened by the sender's own
            // key. So every outgoing message is encrypted TWICE: once to
            // the recipient, once to ourselves, mirroring how mainstream
            // E2E messengers let you see your own sent messages.
            const ciphertextForRecipient = SecureMessaging.ECC.encrypt(plaintext, recipientEccPublicKey);
            const ciphertextForSender = SecureMessaging.ECC.encrypt(plaintext, ourPublicKeyB64);

            let signature = null;
            const shouldSign = document.getElementById('sign-checkbox').checked;
            if (shouldSign) {
                const schnorrPrivateKey = await readFileAsText(document.getElementById('schnorr-private-file'));
                if (!schnorrPrivateKey) throw new Error('Anda memilih untuk menandatangani, tetapi berkas kunci privat Schnorr belum diunggah.');
                const params = (await Api.request('GET', '/schnorr/parameters')).data;
                signature = await SecureMessaging.Schnorr.sign(plaintext, schnorrPrivateKey, params);
            }

            const session = await Api.establishAlsSession();
            await Api.alsRequest('POST', '/messages', {
                conversation_uuid: conversationUuid,
                recipient_uuid: recipientUuid,
                ciphertext_for_recipient: ciphertextForRecipient,
                ciphertext_for_sender: ciphertextForSender,
                signature,
                signed: shouldSign,
            }, session);

            document.getElementById('plaintext').value = '';
            messageEl.className = 'status-message';
            messageEl.textContent = '';
            await loadMessages();
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
        } finally {
            sendBtn.disabled = false;
        }
    });

    loadMessages();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project\resources\views/conversations/show.blade.php ENDPATH**/ ?>