@extends('auth.layouts.app')

@section('content')
    <div class="h-100 bg-plum-plate bg-animation">
        <div class="d-flex h-100 justify-content-center align-items-center">
            <div class="mx-auto app-login-box col-md-8">
                <div class="app-logo-inverse mx-auto mb-3"></div>
                <div class="modal-dialog w-100 mx-auto">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="modal-body">
                                <div class="h5 modal-title text-center">
                                    <h4 class="mt-2">
                                        <div>Welcome back,</div>
                                        <span>Please sign in to your account below.</span>
                                    </h4>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-12">
                                        <div class="position-relative form-group">
                                            <label for="text">Email or Username</label>
                                            <input name="text" id="text" placeholder="{{ trans('common.email_or_username') }}" type="text"
                                                class="form-control @error('text') is-invalid @enderror">

                                            @error('text')
                                                <span class="text-danger" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                        </div>

                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-12">
                                        <div class="position-relative form-group">
                                            <label for="password">Password</label>

                                            <div class="position-relative">
                                                <input name="password" id="password" placeholder="{{ trans('common.password') }}"
                                                    type="password"
                                                    class="form-control pr-5 @error('password') is-invalid @enderror">

                                                <span toggle="#password" class="toggle-password fa fa-eye"></span>
                                            </div>

                                            @error('password')
                                                <span class="text-danger" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer clearfix">
                                {{-- <div class="float-left">
                                    <a href="{{ url('/register') }}" class="btn btn-white text-primary border btn-lg">
                                        Register Owner <i class="fa fa-building"></i>
                                    </a>
                                </div> --}}
                                <div class="float-right">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Login <i class="fas fa-sign-in-alt ml-1"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center text-white opacity-8 mt-3">Copyright © Bio Experience {{ date('Y') }}</div>
            </div>
        </div>
    </div>
@endsection
