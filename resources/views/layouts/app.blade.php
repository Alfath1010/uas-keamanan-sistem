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
        // crypto.js is a module and loads asynchronously; give it a moment,
        // then check whether it (and its libsodium import) actually landed.
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
    <div class="app-shell">
        <nav>
            <a href="{{ url('/conversations') }}" class="{{ request()->is('conversations*') ? 'active' : '' }}">Percakapan</a>
            <span class="sep">&middot;</span>
            <a href="{{ url('/keys') }}" class="{{ request()->is('keys*') ? 'active' : '' }}">Kunci</a>
            <span class="sep">&middot;</span>
            <a href="#" id="nav-logout">Keluar</a>
            <span class="sep">&middot;</span>
            Masuk sebagai: <code id="nav-self-email">(tidak diketahui)</code>
            <span id="nav-status"></span>
        </nav>

        <main>
            @yield('content')
        </main>
    </div>

    <script>
        // Redirect to login if not authenticated, on every page except auth pages themselves.
        if (!window.location.pathname.startsWith('/login') && !window.location.pathname.startsWith('/register')) {
            if (!Api.isLoggedIn()) {
                window.location.href = '/login';
            }
        }

        document.getElementById('nav-logout').addEventListener('click', async (e) => {
            e.preventDefault();
            try { await Api.request('POST', '/logout'); } catch (err) { /* ignore */ }
            Api.clearToken();
            window.location.href = '/login';
        });

        const selfEmail = Api.getSelfEmail();
        document.getElementById('nav-self-email').textContent =
            selfEmail || '(keluar lalu masuk kembali untuk melihat ini)';
    </script>
    @yield('scripts')
</body>
</html>
