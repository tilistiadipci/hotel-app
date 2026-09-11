@php
    $page = 'server-error';
@endphp

@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon">
                        <i class="fa fa-exclamation-triangle icon-gradient bg-mean-fruit"></i>
                    </div>
                    <div>
                        Terjadi Kesalahan
                        <div class="page-title-subheading">Server tidak dapat memproses permintaan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-5 text-center">
                        <img src="{{ getMediaImageUrl('default/error.png', 300, 300) }}" class="img-fluid" alt="" style="opacity: 0.5">
                        <h2 class="text-center my-3">Terjadi Kesalahan</h2>
                        <p class="text-muted">Silakan coba kembali. Jika masalah berlanjut, hubungi administrator platform.</p>
                        <button class="btn btn-outline-primary" onclick="window.location.href = '{{ url('/') }}'">
                            <i class="metismenu-icon lnr-laptop"></i> Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
