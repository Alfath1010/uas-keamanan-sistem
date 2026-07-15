@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1>Masuk</h1>
    <form id="login-form">
        <label>Email<input type="email" id="email" required></label>
        <label>Kata Sandi<input type="password" id="password" required></label>
        <button type="submit" id="submit-btn">Masuk</button>
    </form>
    <p id="message" class="status-message"></p>
    <p><a class="brand" href="{{ url('/register') }}">Belum punya akun?</a></p>
@endsection

@section('scripts')
<script type="module">
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageEl = document.getElementById('message');
        const submitBtn = document.getElementById('submit-btn');

        messageEl.className = 'status-message pending';
        messageEl.textContent = 'Sedang masuk...';
        submitBtn.disabled = true;

        try {
            const result = await Api.request('POST', '/login', {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            });
            Api.setToken(result.data.token);
            Api.setSelfUuid(result.data.uuid);
            Api.setSelfEmail(document.getElementById('email').value);
            window.location.href = '/conversations';
        } catch (err) {
            messageEl.className = 'status-message error';
            messageEl.textContent = `Kesalahan (${err.errorCode || err.statusCode}): ${err.message}`;
            submitBtn.disabled = false;
        }
    });
</script>
@endsection
