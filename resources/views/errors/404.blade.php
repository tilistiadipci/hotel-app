@php
    $page = 'not-found';
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
                        Not Found
                        <div class="page-title-subheading"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-center py-3">
                <img src="{{ getMediaImageUrl('default/error.png', 300, 300) }}" class="img-fluid" alt=""
                    style="opacity: 0.5">
                <h2 class="text-center my-3">Page Not Found</h2>
                <button class="btn btn-outline-primary" onclick="window.location.href = '{{ url('/') }}'">
                    <i class="metismenu-icon lnr-laptop"></i> Dashboard
                </button>
            </div>
        </div>
    </div>
@endsection
