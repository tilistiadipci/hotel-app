@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Tambah Master Paket', 'icon' => $icon, 'breadcrumbs' => [['href' => route('platform.master-paket.index'), 'label' => 'Master Paket'], ['href' => '#', 'label' => 'Tambah']]])
    </div></div>
    <div class="card">@include('pages.platform.master_paket.form')</div>
</div>
@endsection
