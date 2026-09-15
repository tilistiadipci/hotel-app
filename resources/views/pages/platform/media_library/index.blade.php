@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Media Library',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => 'Media Library'],
                    ],
                ])
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <p class="text-muted mb-3">
                    Media library setiap hotel terpisah satu sama lain. Pilih hotel untuk melihat/kelola file media miliknya
                    (pilih <strong>Master Data</strong> untuk media yang dipakai sebagai template hotel baru).
                </p>
                <form action="{{ route('platform.hotel-context.update') }}" method="POST" class="form-inline">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ route('media.index') }}">
                    <label class="mr-2 font-weight-bold" for="hotel_id">Hotel</label>
                    <select name="hotel_id" id="hotel_id" class="form-control mr-2 select2" style="min-width: 280px" onchange="this.form.submit()">
                        @foreach ($hotels as $hotel)
                            <option value="{{ $hotel->id }}" @selected((string) $activeHotelId === (string) $hotel->id)>
                                {{ $hotel->is_system ? 'Master Data' : $hotel->name }}
                            </option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-primary">Tampilkan</button></noscript>
                </form>
            </div>
        </div>
    </div>
@endsection
