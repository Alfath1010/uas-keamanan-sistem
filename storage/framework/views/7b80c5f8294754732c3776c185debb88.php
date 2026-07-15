<?php $__env->startSection('title', 'Daftar'); ?>

<?php $__env->startSection('content'); ?>
    <h1>Daftar</h1>
    <form id="register-form">
        <label>Nama<input type="text" id="name" required></label>
        <label>Email<input type="email" id="email" required></label>
        <label>Kata Sandi (minimal 8 karakter)<input type="password" id="password" required minlength="8"></label>
        <label>Konfirmasi Kata Sandi<input type="password" id="password_confirmation" required></label>
        <button type="submit" id="submit-btn">Daftar</button>
    </form>
    <p id="message" class="status-message"></p>
    <p><a class="brand" href="<?php echo e(url('/login')); ?>">Sudah punya akun? Masuk</a></p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script type="module">
    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageEl = document.getElementById('message');
        const submitBtn = document.getElementById('submit-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Mendaftarkan...';
        submitBtn.disabled = true;

        try {
            await Api.request('POST', '/register', {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value,
            });
            messageEl.className = 'status-message success';
            messageEl.textContent = 'Akun berhasil dibuat. Mengalihkan ke halaman masuk...';
            setTimeout(() => window.location.href = '/login', 1000);
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
            submitBtn.disabled = false;
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\project\resources\views/auth/register.blade.php ENDPATH**/ ?>