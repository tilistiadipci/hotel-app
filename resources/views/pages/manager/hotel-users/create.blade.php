@extends('templates.index')
@section('content')
<div class="app-main__inner"><div class="app-page-title"><div class="page-title-wrapper">@include('templates.parts.breadcrumb', ['title'=>'Buat Admin Hotel','icon'=>$icon,'breadcrumbs'=>[['href'=>route('manager.hotel-users.index'),'label'=>'User Hotel'],['href'=>'#','label'=>'Buat Admin']]])</div></div><div class="card">@include('pages.manager.hotel-users.form')</div></div>
@endsection
