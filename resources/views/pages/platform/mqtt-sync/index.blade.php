@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title">
        <div class="page-title-wrapper">
            @include('templates.parts.breadcrumb', ['title' => 'Sync to TV MQTT', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Sync to TV MQTT']]])
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card">
        <div class="card-header"><strong>Kirim Perintah Sinkronisasi</strong></div>
        <div class="card-body">
            <p class="text-muted">Pilih hotel dan jenis data. Kosongkan player untuk mengirim satu per satu ke setiap serial player aktif pada hotel tersebut.</p>
            <form method="POST" action="{{ route('platform.mqtt-sync.store') }}" id="mqtt-sync-form">
                @csrf
                <div class="form-group">
                    <label for="hotel_id">Hotel <span class="text-danger">*</span></label>
                    <select name="hotel_id" id="hotel_id" class="form-control" required data-player-url="{{ route('platform.mqtt-sync.players', ['hotel' => '__HOTEL__']) }}">
                        <option value="">Pilih hotel</option>
                        @foreach($hotels as $hotel)
                            <option value="{{ $hotel->id }}" @selected(old('hotel_id') === $hotel->id)>{{ $hotel->name }} ({{ $hotel->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="player_id">Player <small class="text-muted">(opsional)</small></label>
                    <select name="player_id" id="player_id" class="form-control" disabled>
                        <option value="">Semua player dalam hotel</option>
                    </select>
                    <small class="form-text text-muted">Jika tidak dipilih, backend melakukan fan-out ke topic <code>players/{player_serial}/update</code> milik setiap player aktif.</small>
                </div>
                <div class="form-group">
                    <label for="type">Master data <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="">Pilih jenis data</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }} ({{ $value }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane mr-1"></i>Kirim MQTT</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hotel = document.getElementById('hotel_id');
    const player = document.getElementById('player_id');
    const oldPlayer = @json((string) old('player_id'));

    async function loadPlayers() {
        player.disabled = true;
        player.innerHTML = '<option value="">Semua player dalam hotel</option>';
        if (!hotel.value) return;

        const url = hotel.dataset.playerUrl.replace('__HOTEL__', encodeURIComponent(hotel.value));
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Gagal memuat player');
            const data = await response.json();
            data.players.forEach(function (item) {
                const option = new Option(item.name + ' (' + item.serial + ')', item.id, false, String(item.id) === oldPlayer);
                player.add(option);
            });
            player.disabled = false;
        } catch (error) {
            player.innerHTML = '<option value="">Player gagal dimuat</option>';
        }
    }

    hotel.addEventListener('change', loadPlayers);
    loadPlayers();
});
</script>
@endsection
