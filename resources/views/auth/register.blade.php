@extends('auth.layouts.app')

@section('css')
    <style>
        :root { --auth-ink:#17243b; --auth-muted:#718096; --auth-primary:#3169df; }
        body { margin:0; background:#eef3fa; color:var(--auth-ink); }
        .registration-shell { min-height:100vh; display:grid; grid-template-columns:minmax(480px,.9fr) minmax(520px,1.1fr); }
        .registration-panel { display:flex; flex-direction:column; justify-content:center; padding:48px clamp(30px,6vw,92px); background:#fff; }
        .registration-wrap { width:100%; max-width:540px; margin:auto; }
        .registration-brand { display:flex; align-items:center; gap:12px; margin-bottom:45px; color:var(--auth-ink); text-decoration:none; }
        .registration-brand-mark { width:44px; height:44px; display:grid; place-items:center; border-radius:13px; color:#fff; background:linear-gradient(135deg,#3678ed,#7048cf); box-shadow:0 10px 24px rgba(49,105,223,.25); }
        .registration-brand-copy strong,.registration-brand-copy small { display:block; font-style:normal!important; transform:none!important; }
        .registration-brand-copy strong { font-size:18px; line-height:1.1; font-weight:800; }
        .registration-brand-copy small { color:var(--auth-muted); letter-spacing:.08em; text-transform:uppercase; }
        .registration-kicker { color:var(--auth-primary); font-size:12px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; margin-bottom:10px; }
        .registration-title { margin:0 0 10px; color:var(--auth-ink); font-size:clamp(32px,4vw,44px); font-weight:700; letter-spacing:-.035em; }
        .registration-subtitle { margin:0 0 30px; color:var(--auth-muted); line-height:1.7; }
        .registration-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .registration-field-wide { grid-column:1/-1; }
        .registration-field label { display:block; margin-bottom:8px; color:#34435a; font-weight:600; }
        .registration-section-title { grid-column:1/-1; margin-top:8px; padding-bottom:9px; border-bottom:1px solid #e8edf5; color:#243754; font-size:14px; font-weight:800; }
        .registration-required { color:#dc3545; }
        .registration-input { width:100%; height:52px; border:1px solid #dce4ef; border-radius:13px; padding:0 15px; background:#f8fafd; color:var(--auth-ink); box-sizing:border-box; transition:.2s; }
        textarea.registration-input { height:auto; min-height:90px; padding-top:13px; resize:vertical; }
        .registration-input:focus { outline:0; border-color:#6c98ef; background:#fff; box-shadow:0 0 0 4px rgba(49,105,223,.1); }
        .registration-input.is-invalid { border-color:#dc3545; }
        .registration-error { display:block; margin-top:5px; color:#dc3545; font-size:12px; }
        .registration-submit { width:100%; height:54px; margin-top:25px; border:0; border-radius:13px; color:#fff; background:linear-gradient(135deg,var(--auth-primary),#6555d9); box-shadow:0 12px 28px rgba(49,105,223,.24); font-weight:700; cursor:pointer; }
        .registration-login { margin-top:20px; color:var(--auth-muted); text-align:center; }
        .registration-login a { color:var(--auth-primary); font-weight:700; }
        .registration-story { position:relative; overflow:hidden; min-height:100vh; background:#17243b url("{{ asset('images/auth/hotel-lobby-pexels.jpg') }}") center/cover no-repeat; }
        .registration-story::before { content:''; position:absolute; inset:0; background:linear-gradient(145deg,rgba(14,35,70,.92),rgba(35,80,143,.74) 48%,rgba(96,55,163,.84)); }
        .registration-story-content { position:relative; z-index:1; min-height:100vh; display:flex; flex-direction:column; justify-content:flex-end; padding:clamp(48px,8vw,110px); color:#fff; }
        .registration-story-content h2 { max-width:650px; margin:0 0 18px; color:#fff; font-size:clamp(38px,5vw,64px); line-height:1.06; letter-spacing:-.04em; }
        .registration-story-content p { max-width:590px; margin:0; color:rgba(255,255,255,.82); font-size:16px; line-height:1.75; }
        @media(max-width:991px){.registration-shell{grid-template-columns:1fr}.registration-story{display:none}.registration-panel{padding:42px 26px}}
        @media(max-width:575px){.registration-grid{grid-template-columns:1fr}.registration-field-wide{grid-column:auto}.registration-title{font-size:34px}}
    </style>
@endsection

@section('content')
    <main class="registration-shell">
        <section class="registration-panel">
            <div class="registration-wrap">
                <a class="registration-brand" href="{{ route('landing.show') }}">
                    <span class="registration-brand-mark"><i class="fa fa-building"></i></span>
                    <span class="registration-brand-copy"><strong>{{ config('app.name', 'Hotel App') }}</strong><small>{{ __('platform.registration.brand_subtitle') }}</small></span>
                </a>
                <div class="registration-kicker">{{ __('platform.registration.kicker') }}</div>
                <h1 class="registration-title">{{ __('platform.registration.title') }}</h1>
                <p class="registration-subtitle">{{ __('platform.registration.subtitle') }}</p>
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="registration-grid">
                        <div class="registration-section-title">{{ __('platform.registration.hotel_data') }}</div>
                        <div class="registration-field registration-field-wide">
                            <label for="hotel_name">{{ __('platform.registration.hotel_name') }} <span class="registration-required">*</span></label>
                            <input id="hotel_name" name="hotel_name" value="{{ old('hotel_name') }}" class="registration-input @error('hotel_name') is-invalid @enderror" required autofocus>
                            @error('hotel_name')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field registration-field-wide">
                            <label for="hotel_address">{{ __('platform.registration.hotel_address') }} <span class="registration-required">*</span></label>
                            <textarea id="hotel_address" name="hotel_address" class="registration-input @error('hotel_address') is-invalid @enderror" required>{{ old('hotel_address') }}</textarea>
                            @error('hotel_address')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-section-title">{{ __('platform.registration.manager_account_data') }}</div>
                        <div class="registration-field registration-field-wide">
                            <label for="person_in_charge">{{ __('platform.registration.person_in_charge') }} <span class="registration-required">*</span></label>
                            <input id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge') }}" class="registration-input @error('person_in_charge') is-invalid @enderror" required>
                            @error('person_in_charge')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field registration-field-wide">
                            <label for="username">{{ __('platform.registration.username') }} <span class="registration-required">*</span></label>
                            <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" class="registration-input @error('username') is-invalid @enderror" required>
                            @error('username')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field">
                            <label for="email">{{ __('platform.registration.email') }} <span class="registration-required">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="registration-input @error('email') is-invalid @enderror" required>
                            @error('email')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field">
                            <label for="whatsapp">{{ __('platform.registration.whatsapp') }} <span class="registration-required">*</span></label>
                            <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="08123456789" class="registration-input @error('whatsapp') is-invalid @enderror" required>
                            @error('whatsapp')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field">
                            <label for="gender">{{ __('platform.registration.gender') }}</label>
                            <select id="gender" name="gender" class="registration-input @error('gender') is-invalid @enderror">
                                <option value="">{{ __('platform.registration.choose_gender') }}</option>
                                <option value="male" @selected(old('gender') === 'male')>{{ __('platform.registration.male') }}</option>
                                <option value="female" @selected(old('gender') === 'female')>{{ __('platform.registration.female') }}</option>
                            </select>
                            @error('gender')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field registration-field-wide">
                            <label for="manager_address">{{ __('platform.registration.manager_address') }}</label>
                            <textarea id="manager_address" name="manager_address" class="registration-input @error('manager_address') is-invalid @enderror">{{ old('manager_address') }}</textarea>
                            @error('manager_address')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field">
                            <label for="password">{{ __('platform.registration.password') }} <span class="registration-required">*</span></label>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="registration-input @error('password') is-invalid @enderror" required>
                            @error('password')<span class="registration-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="registration-field">
                            <label for="password_confirmation">{{ __('platform.registration.password_confirmation') }} <span class="registration-required">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="registration-input" required>
                        </div>
                    </div>
                    <button type="submit" class="registration-submit">{{ __('platform.registration.submit') }}</button>
                </form>
                <div class="registration-login">{{ __('platform.registration.have_account') }} <a href="{{ route('login') }}">{{ __('platform.registration.login') }}</a></div>
            </div>
        </section>
        <aside class="registration-story">
            <div class="registration-story-content">
                <h2>{{ __('platform.registration.story_title') }}</h2>
                <p>{{ __('platform.registration.story_text') }}</p>
            </div>
        </aside>
    </main>
@endsection
