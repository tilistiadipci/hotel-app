@extends('templates.index')

@section('content')
<div class="app-main__inner manager-overview">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title'=>'Dashboard Manager','icon'=>$icon,'breadcrumbs'=>[['href'=>'#','label'=>'Ringkasan seluruh hotel']]])
        <div class="page-title-actions">
            <form method="GET" action="{{ route('manager.dashboard') }}" class="form-inline" data-no-loading>
                <input name="daterange" class="form-control form-control-sm manager-daterange mr-2" value="{{ $dateRange }}" autocomplete="off">
                <button class="btn btn-sm btn-primary"><i class="fa fa-filter mr-1"></i>Filter</button>
                <a href="{{ route('manager.dashboard') }}" class="btn btn-sm btn-light ml-2"><i class="fa fa-undo"></i></a>
            </form>
        </div>
    </div></div>

    <div class="row">
        @foreach([
            ['Hotel Aktif', $totals['active_hotels'].' / '.$totals['hotels'], 'fa-hotel', 'primary'],
            ['Total Player', $totals['players'], 'fa-tv', 'info'],
            ['Tamu Aktif', $totals['active_guests'], 'fa-user-clock', 'success'],
            ['Check-in', $totals['checkins'], 'fa-sign-in-alt', 'warning'],
            ['Check-out', $totals['checkouts'], 'fa-sign-out-alt', 'alternate'],
            ['Transaksi', $totals['transactions'], 'fa-receipt', 'danger'],
        ] as [$label,$value,$statIcon,$color])
        <div class="col-sm-6 col-lg-4 col-xl-2"><div class="card metric-card mb-2"><div class="card-body d-flex align-items-center px-3 py-2">
            <span class="metric-icon bg-{{ $color }}"><i class="fa {{ $statIcon }}"></i></span>
            <div><div class="metric-value font-weight-bold">{{ is_numeric($value) ? number_format((float) $value) : $value }}</div><small class="text-muted">{{ $label }}</small></div>
        </div></div></div>
        @endforeach
    </div>

    <div class="row dashboard-feature-row mt-2">
        <div class="col-lg-8"><div class="card mb-3">
            <div class="card-header"><strong>Tren Check-in & Check-out</strong><span class="ml-auto text-muted small">{{ $dateRange }}</span></div>
            <div class="card-body py-2"><div id="managerBookingChart" style="height:235px"></div></div>
        </div></div>
        <div class="col-lg-4"><div class="card mb-3">
            <div class="card-header"><strong>Nilai Transaksi Tenant</strong></div>
            <div class="card-body d-flex flex-column justify-content-center text-center py-3">
                <i class="fa fa-wallet fa-2x text-success mb-2"></i>
                <div class="h4 font-weight-bold mb-0">Rp {{ number_format($totals['revenue'], 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($totals['transactions']) }} transaksi non-batal</small>
                <a href="{{ route('manager.portfolio') }}" class="btn btn-sm btn-outline-primary mt-3">Buka Portfolio Hotel</a>
            </div>
        </div></div>
    </div>

    <div class="row mt-2">
        <div class="col-lg-6"><div class="card mb-3">
            <div class="card-header"><strong>Aktivitas per Hotel</strong></div>
            <div class="card-body py-2"><div id="managerHotelChart" style="height:245px"></div></div>
        </div></div>
        <div class="col-lg-6"><div class="card mb-3">
            <div class="card-header"><strong>Transaksi Tenant per Hotel</strong></div>
            <div class="card-body py-2"><div id="managerTransactionChart" style="height:245px"></div></div>
        </div></div>
    </div>

    <div class="row">
        <div class="col-lg-7"><div class="card mb-3">
            <div class="card-header"><strong>Performa Hotel</strong></div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>Hotel</th><th class="text-center">Player</th><th class="text-center">Check-in</th><th class="text-center">Check-out</th></tr></thead>
                <tbody>@forelse($hotelActivity as $hotel)<tr><td>{{ $hotel['name'] }}</td><td class="text-center">{{ $hotel['players'] }}</td><td class="text-center">{{ $hotel['checkins'] }}</td><td class="text-center">{{ $hotel['checkouts'] }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada hotel yang dikelola.</td></tr>@endforelse</tbody>
            </table></div>
        </div></div>
        <div class="col-lg-5"><div class="card mb-3">
            <div class="card-header"><strong>Top Tenant</strong></div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>Tenant / Hotel</th><th class="text-right">Transaksi</th><th class="text-right">Nilai</th></tr></thead>
                <tbody>@forelse($topTenants as $tenant)<tr><td><strong>{{ $tenant->name }}</strong><small class="d-block text-muted">{{ $tenant->hotel_name }}</small></td><td class="text-right">{{ number_format($tenant->total) }}</td><td class="text-right">Rp {{ number_format($tenant->revenue, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada transaksi pada periode ini.</td></tr>@endforelse</tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection

@section('css')
<style>
.manager-overview .metric-card { border:0; box-shadow:0 .35rem 1.2rem rgba(30,45,75,.08); }
.manager-overview .metric-card .card-body { min-height:60px; }
.manager-overview .metric-icon { width:36px;height:36px;border-radius:10px;color:#fff;display:flex;align-items:center;justify-content:center;margin-right:10px;flex:0 0 36px; }
.manager-overview .metric-value { font-size:17px; line-height:1.15; }
.manager-overview .card-header { min-height:42px; padding-top:.55rem; padding-bottom:.55rem; }
.manager-overview .dashboard-feature-row > [class*="col-"] { display:flex; }
.manager-overview .dashboard-feature-row > [class*="col-"] > .card { flex:1 1 auto; }
</style>
@endsection

@section('js')
<script src="{{ asset('js/highcharts.min.js') }}"></script>
<script>
(function () {
    const booking = @json($bookingChart);
    const hotels = @json($hotelActivity);
    const transactions = @json($transactionByHotel);

    $('.manager-daterange').daterangepicker({ autoUpdateInput: true, locale: { format: 'DD/MM/YYYY' }, opens: 'left' });
    Highcharts.chart('managerBookingChart', { chart:{type:'areaspline'}, title:{text:null}, xAxis:{categories:booking.labels}, yAxis:{min:0,title:{text:'Jumlah'}}, credits:{enabled:false}, series:[{name:'Check-in',data:booking.checkins,color:'#f7b924'},{name:'Check-out',data:booking.checkouts,color:'#794c8a'}] });
    Highcharts.chart('managerHotelChart', { chart:{type:'column'}, title:{text:null}, xAxis:{categories:hotels.map(h=>h.name)}, yAxis:{min:0,title:{text:'Jumlah'}}, credits:{enabled:false}, series:[{name:'Player',data:hotels.map(h=>h.players),color:'#16aaff'},{name:'Check-in',data:hotels.map(h=>h.checkins),color:'#3ac47d'},{name:'Check-out',data:hotels.map(h=>h.checkouts),color:'#794c8a'}] });
    Highcharts.chart('managerTransactionChart', { chart:{type:'bar'}, title:{text:null}, xAxis:{categories:transactions.map(h=>h.name)}, yAxis:{min:0,title:{text:'Transaksi'}}, credits:{enabled:false}, legend:{enabled:false}, series:[{name:'Transaksi',data:transactions.map(h=>Number(h.total)),color:'#d92550'}] });
})();
</script>
@endsection
