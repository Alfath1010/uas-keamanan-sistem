<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Aplikasi Pesan Aman'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <script type="module" src="<?php echo e(asset('js/crypto.js')); ?>"></script>
    <script src="<?php echo e(asset('js/api.js')); ?>"></script>
    <script>
        setTimeout(() => {
            if (typeof window.SecureMessaging === 'undefined' || typeof window.sodium === 'undefined') {
                document.body.insertAdjacentHTML('afterbegin',
                    '<p class="status-message error">Gagal memuat modul kripto (crypto.js atau impor libsodium-nya) — ' +
                    'periksa tab Network/Console di konsol peramban Anda. Halaman ini tidak akan berfungsi tanpanya.</p>'
                );
            }
        }, 3000);
    </script>
</head>
<body>
    <div class="guest-shell">
        <div class="auth-card">
            <main>
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\project\resources\views/layouts/guest.blade.php ENDPATH**/ ?>