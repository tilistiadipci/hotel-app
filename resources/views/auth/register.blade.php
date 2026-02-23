@extends('auth.layouts.app')

@section('content')
    <div class="h-100">
        <div class="h-100 no-gutters row">
            <div class="h-100 d-md-flex d-sm-block bg-white justify-content-center align-items-center col-md-12 col-lg-7">
                <div class="mx-auto app-login-box col-sm-12 col-md-10 col-lg-9">
                    <h4>
                        <div>Create Owner</div>
                    </h4>
                    <div>
                        <form class="" method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="position-relative form-group"><label for="email" class=""><span
                                                class="text-danger">*</span> Email</label><input name="email"
                                            id="email" placeholder="Email here..." type="email"
                                            class="form-control"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="position-relative form-group"><label for="name"
                                            class="">Name</label><input name="name" id="name"
                                            placeholder="Name here..." type="text" class="form-control"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="position-relative form-group"><label for="examplePassword"
                                            class=""><span class="text-danger">*</span> Password</label><input
                                            name="password" id="examplePassword" placeholder="Password here..."
                                            type="password" class="form-control"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="position-relative form-group"><label for="domainName"
                                            class=""><span class="text-danger">*</span> Domain Name</label><input
                                            name="domain" id="domainName" placeholder="Domain name here..."
                                            type="text" class="form-control"></div>
                                </div>
                            </div>
                            <div class="mt-3 position-relative form-check"><input name="check" id="exampleCheck"
                                    type="checkbox" class="form-check-input"><label for="exampleCheck"
                                    class="form-check-label">Accept our <a href="javascript:void(0);">Terms
                                        and Conditions</a>.</label></div>
                            <div class="mt-4 d-flex align-items-center">
                                <h5 class="mb-0">Already have an account? <a href="javascript:void(0);"
                                        class="text-primary">Sign in</a></h5>
                                <div class="ml-auto">
                                    <button
                                        class="btn-wide btn-pill btn-shadow btn-hover-shine btn btn-primary btn-lg">Create
                                        Account</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-lg-flex d-xs-none col-lg-5">
                <div class="slider-light">
                    <div class="slick-slider slick-initialized">
                        <div>
                            <div class="position-relative h-100 d-flex justify-content-center align-items-center bg-premium-dark"
                                tabindex="-1">
                                <div class="slide-img-bg" style="background-image: url('../template/assets/images/originals/citynights.jpg');"></div>
                                <div class="slider-content">
                                    <h3>Scalable, Modular, Consistent</h3>
                                    <p>Easily exclude the components you don't require. Lightweight, consistent Bootstrap
                                        based styles across all elements and components</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> --}}
@endsection
