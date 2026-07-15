<?php $__env->startSection('title', 'Percakapan'); ?>

<?php $__env->startSection('content'); ?>
    <h1>Percakapan</h1>

    <h2>Mulai percakapan baru</h2>
    <form id="new-conversation-form">
        <label>Email Penerima<input type="email" id="recipient_email" required placeholder="temanku@emai.com"></label>
        <button type="submit" id="start-btn">Mulai</button>
    </form>
    <p id="new-conversation-message" class="status-message"></p>

    <h2>Percakapan Anda</h2>
    <button id="refresh-btn" class="secondary">Muat Ulang</button>
    <ul id="conversation-list" class="conversation-list"></ul>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script type="module">
    async function loadConversations() {
        const list = document.getElementById('conversation-list');
        list.innerHTML = '<li class="hint">Memuat...</li>';

        try {
            const result = await Api.request('GET', '/conversations');
            const conversations = result.data.conversations;

            if (conversations.length === 0) {
                list.innerHTML = '<li class="hint">Belum ada percakapan.</li>';
                return;
            }

            list.innerHTML = '';
            for (const conv of conversations) {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = `/conversations/${conv.uuid}`;
                const selfUuid = Api.getSelfUuid();
                const other = conv.members.find(m => m.uuid !== selfUuid);
                // Point 1: show "Nama (email)" rather than just the email,
                // now that the API returns each member's name too.
                a.textContent = other ? `${other.name} (${other.email})` : '(tidak diketahui)';
                li.appendChild(a);
                list.appendChild(li);
            }
        } catch (err) {
            list.innerHTML = `<li class="status-message error">Kesalahan (${err.errorCode || err.statusCode}): ${err.message}</li>`;
        }
    }

    document.getElementById('refresh-btn').addEventListener('click', loadConversations);

    document.getElementById('new-conversation-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageEl = document.getElementById('new-conversation-message');
        const startBtn = document.getElementById('start-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Membuat...';
        startBtn.disabled = true;

        try {
            const result = await Api.request('POST', '/conversations', {
                recipient_email: document.getElementById('recipient_email').value,
            });
            window.location.href = `/conversations/${result.data.uuid}`;
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
            startBtn.disabled = false;
        }
    });

    loadConversations();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project\resources\views/conversations/index.blade.php ENDPATH**/ ?>