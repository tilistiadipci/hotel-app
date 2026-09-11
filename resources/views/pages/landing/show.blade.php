<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landingPage->meta_title }}</title>
    @if($landingPage->meta_description)<meta name="description" content="{{ $landingPage->meta_description }}">@endif
    @if($landingPage->meta_keywords)<meta name="keywords" content="{{ $landingPage->meta_keywords }}">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $landingPage->site_name }}">
    <meta property="og:title" content="{{ $landingPage->meta_title }}">
    @if($landingPage->meta_description)<meta property="og:description" content="{{ $landingPage->meta_description }}">@endif
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="{{ $logoUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $landingPage->meta_title }}">
    @if($landingPage->meta_description)<meta name="twitter:description" content="{{ $landingPage->meta_description }}">@endif
    <link rel="canonical" href="{{ url('/') }}">
    @if($logoUrl)
        <meta property="og:image" content="{{ $logoUrl }}">
        <link rel="icon" href="{{ $logoUrl }}">
    @endif
    <meta name="robots" content="index, follow">
</head>
<body style="margin:0">
    {!! $html !!}
</body>
</html>
