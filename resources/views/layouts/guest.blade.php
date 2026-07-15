<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Aplikasi Pesan Aman')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script type="module" src="{{ asset('js/crypto.js') }}"></script>
    <script src="{{ asset('js/api.js') }}"></script>
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
                @yield('content')
            </main>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
