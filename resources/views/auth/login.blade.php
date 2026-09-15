@extends('auth.layouts.app')

@section('css')
    <style>
        :root { --auth-ink:#17243b; --auth-muted:#718096; --auth-primary:#3169df; }
        body { margin:0; background:#eef3fa; color:var(--auth-ink); }
        .auth-shell { min-height:100vh; display:grid; grid-template-columns:minmax(520px,1.35fr) minmax(420px,.9fr); }
        .auth-form-panel { display:flex; flex-direction:column; justify-content:center; padding:56px clamp(36px,7vw,110px); background:#fff; position:relative; z-index:2; }
        .auth-brand { display:flex; align-items:center; gap:12px; margin-bottom:64px; color:var(--auth-ink); }
        .auth-brand-mark { width:44px; height:44px; display:grid; place-items:center; border-radius:13px; color:#fff; background:linear-gradient(135deg,#3678ed,#7048cf); box-shadow:0 10px 24px rgba(49,105,223,.25); }
        .auth-brand-copy strong,.auth-brand-copy small { display:block; }
        .auth-brand-copy strong { font-size:18px; line-height:1.1; font-style:normal!important; transform:none!important; font-weight:800; }
        .auth-brand-copy small { color:var(--auth-muted); letter-spacing:.08em; text-transform:uppercase; }
        .auth-form-wrap { width:100%;  margin:auto; }
        .auth-kicker { color:var(--auth-primary); font-size:12px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; margin-bottom:12px; }
        .auth-title { margin:0 0 10px; font-size:clamp(34px,4vw,48px); font-weight:700; letter-spacing:-.035em; color:var(--auth-ink); }
        .auth-subtitle { margin:0 0 38px; color:var(--auth-muted); font-size:15px; line-height:1.7; }
        .auth-field { margin-bottom:21px; }
        .auth-field label { display:block; margin-bottom:9px; color:#34435a; font-weight:600; }
        .auth-input-wrap { position:relative; }
        .auth-input { width:100%; height:54px; border:1px solid #dce4ef; border-radius:13px; padding:0 48px 0 16px; background:#f8fafd; color:var(--auth-ink); transition:.2s; box-sizing:border-box; }
        .auth-input:focus { outline:0; border-color:#6c98ef; background:#fff; box-shadow:0 0 0 4px rgba(49,105,223,.1); }
        .auth-input.is-invalid { border-color:#e55353; }
        .auth-input-icon { position:absolute; right:16px; top:50%; transform:translateY(-50%); color:#93a1b5; }
        .toggle-password.auth-input-icon { top:50%; right:16px; }
        .field-error { display:block; min-height:19px; margin-top:6px; color:#dc3545; font-size:12px; }
        .auth-options { display:flex; justify-content:space-between; align-items:center; margin:2px 0 28px; color:var(--auth-muted); font-size:13px; }
        .auth-options label { display:flex; align-items:center; gap:8px; margin:0; cursor:pointer; }
        .auth-submit { width:100%; height:54px; border:0; border-radius:13px; color:#fff; background:linear-gradient(135deg,var(--auth-primary),#6555d9); box-shadow:0 12px 28px rgba(49,105,223,.24); font-size:15px; font-weight:700; transition:.2s; cursor:pointer; }
        .auth-submit:hover { transform:translateY(-1px); box-shadow:0 15px 32px rgba(49,105,223,.3); }
        .auth-submit:disabled { cursor:wait; opacity:.72; transform:none; }
        .auth-spinner { display:none; margin-right:8px; }
        .auth-submit.is-loading .auth-spinner { display:inline-block; }
        .auth-footer { margin-top:56px; color:#9aa7b9; font-size:12px; text-align:center; }
        .auth-alert { margin-bottom:22px; padding:12px 14px; border:1px solid #bce8d5; border-radius:11px; color:#13734f; background:#effbf6; font-size:13px; }
        .auth-register-link { margin-top:20px; color:var(--auth-muted); font-size:13px; text-align:center; }
        .auth-register-link a { color:var(--auth-primary); font-weight:700; }
        .auth-story-panel { position:relative; overflow:hidden; min-height:100vh; background:#17243b url("{{ asset('images/auth/hotel-lobby-pexels.jpg') }}") center/cover no-repeat; }
        .auth-story-panel::before { content:''; position:absolute; inset:0; background:linear-gradient(145deg,rgba(14,35,70,.9),rgba(35,80,143,.72) 48%,rgba(96,55,163,.82)); }
        .auth-story-panel::after { content:''; position:absolute; width:520px; height:520px; right:-210px; bottom:-220px; border:1px solid rgba(255,255,255,.24); border-radius:50%; box-shadow:0 0 0 90px rgba(255,255,255,.035),0 0 0 180px rgba(255,255,255,.025); }
        .auth-story-content { position:relative; z-index:1; height:100%; min-height:100vh; display:flex; flex-direction:column; justify-content:flex-end; padding:clamp(48px,8vw,120px); color:#fff; box-sizing:border-box; }
        .auth-story-badge { width:fit-content; padding:8px 13px; border:1px solid rgba(255,255,255,.3); border-radius:99px; background:rgba(255,255,255,.1); backdrop-filter:blur(10px); font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .auth-story-title { max-width:680px; margin:24px 0 18px; color:#fff; font-size:clamp(40px,5vw,70px); line-height:1.04; letter-spacing:-.04em; text-shadow:0 3px 24px rgba(0,0,0,.2); }
        .auth-story-text { max-width:610px; margin:0; color:rgba(255,255,255,.8); font-size:17px; line-height:1.75; }
        .auth-features { display:flex; flex-wrap:wrap; gap:12px; margin-top:30px; }
        .auth-feature { padding:10px 14px; border-radius:10px; background:rgba(255,255,255,.11); backdrop-filter:blur(8px); font-size:13px; }
        .photo-credit { position:absolute; right:24px; bottom:18px; z-index:2; color:rgba(255,255,255,.62); font-size:10px; }
        .photo-credit:hover { color:#fff; }
        @media(max-width:991px){.auth-shell{grid-template-columns:1fr}.auth-story-panel{min-height:380px}.auth-story-content{min-height:380px;padding:48px 36px}.auth-story-title{font-size:42px}.auth-form-panel{padding:48px 28px}.auth-brand{margin-bottom:42px}}
        @media(max-width:575px){.auth-story-panel,.auth-story-content{min-height:300px}.auth-story-content{padding:34px 24px}.auth-story-title{font-size:34px;margin-top:16px}.auth-story-text,.auth-features{display:none}.auth-form-panel{padding:38px 22px}}
    </style>
@endsection

@section('content')
    <main class="auth-shell">
        <aside class="auth-story-panel" aria-label="Application introduction">
            <div class="auth-story-content">
                <span class="auth-story-badge">Hospitality, connected</span>
                <h2 class="auth-story-title">A better stay begins behind the scenes.</h2>
                <p class="auth-story-text">Deliver consistent in-room entertainment, timely information, and seamless hotel services through one connected platform.</p>
                <div class="auth-features">
                    <span class="auth-feature"><i class="fa fa-tv mr-2"></i>Smart TV Players</span>
                    <span class="auth-feature"><i class="fa fa-concierge-bell mr-2"></i>Guest Services</span>
                    <span class="auth-feature"><i class="fa fa-layer-group mr-2"></i>Centralized Content</span>
                </div>
            </div>
            <a class="photo-credit" href="https://www.pexels.com/photo/high-angle-view-on-people-at-the-reception-desk-in-a-hotel-7512139/" target="_blank" rel="noopener">Photo by Kateryna Naidenko via Pexels</a>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-brand">
                <span class="auth-brand-mark"><i class="fa fa-building"></i></span>
                <span class="auth-brand-copy"><strong>{{ config('app.name', 'Bio Experience') }}</strong><small>Hotel Management System</small></span>
            </div>
            <div class="auth-form-wrap">
                <div class="auth-kicker">Secure CMS Access</div>
                <h1 class="auth-title">Welcome back.</h1>
                <p class="auth-subtitle">Sign in to manage hotel content, guest experiences, players, and operational services from one place.</p>
                @if (session('success'))<div class="auth-alert"><i class="fa fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
                <form id="ajaxLoginForm" method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="auth-field">
                        <label for="text">Email or Username</label>
                        <div class="auth-input-wrap">
                            <input name="text" id="text" value="{{ old('text') }}" autocomplete="username" placeholder="Enter your email or username" type="text" class="auth-input" autofocus>
                            <i class="fa fa-user auth-input-icon"></i>
                        </div>
                        <span class="field-error" data-error-for="text"></span>
                    </div>
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input-wrap">
                            <input name="password" id="password" autocomplete="current-password" placeholder="Enter your password" type="password" class="auth-input">
                            <span toggle="#password" class="toggle-password auth-input-icon fa fa-eye"></span>
                        </div>
                        <span class="field-error" data-error-for="password"></span>
                    </div>
                    <div class="auth-options">
                        <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                        <span>Protected access</span>
                    </div>
                    <button type="submit" class="auth-submit"><i class="fa fa-circle-notch fa-spin auth-spinner"></i><span class="auth-submit-label">Sign in to dashboard</span></button>
                </form>
                <div class="auth-register-link">{{ __('platform.registration.no_account') }} <a href="{{ route('register') }}">{{ __('platform.registration.register_hotel') }}</a></div>
            </div>
            <div class="auth-footer">Copyright &copy; Bio Experience 2024-{{ date('Y') }}</div>
        </section>
    </main>
@endsection

@section('js')
    <script>
        (function() {
            const $form = $('#ajaxLoginForm');
            const $button = $form.find('.auth-submit');
            function clearErrors() { $form.find('.auth-input').removeClass('is-invalid'); $form.find('[data-error-for]').text(''); }
            function showErrors(errors) {
                Object.keys(errors || {}).forEach(function(field) {
                    const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                    $form.find('[name="' + field + '"]').addClass('is-invalid');
                    $form.find('[data-error-for="' + field + '"]').text(message);
                });
            }
            $form.on('submit', function(event) {
                event.preventDefault();
                if ($button.prop('disabled')) return;
                clearErrors();
                $button.prop('disabled', true).addClass('is-loading').find('.auth-submit-label').text('Signing you in...');
                $.ajax({ url:$form.attr('action'), method:'POST', data:$form.serialize(), dataType:'json', headers:{Accept:'application/json'} })
                    .done(function(response) {
                        $button.find('.auth-submit-label').text('Login successful');
                        window.location.assign(response.redirect || '{{ url('/') }}');
                    }).fail(function(xhr) {
                        const payload = xhr.responseJSON || {};
                        showErrors(payload.errors);
                        if (!payload.errors) swal({icon:'error',title:'Login failed',text:payload.message || 'Unable to sign in. Please try again.'});
                        $button.prop('disabled', false).removeClass('is-loading').find('.auth-submit-label').text('Sign in to dashboard');
                    });
            });
        })();
    </script>
@endsection
