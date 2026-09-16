@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', [
            'title' => 'Review Pendaftaran Hotel',
            'icon' => $icon,
            'breadcrumbs' => [
                ['href' => route('platform.registrations.index'), 'label' => __('platform.registration.admin_title')],
                ['href' => '#', 'label' => $registration->hotel_name],
            ],
        ])
    </div></div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Approval belum dapat diproses.</strong>
            <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card registration-wizard">
        <div class="card-header bg-white">
            <div class="wizard-steps">
                @foreach ([1 => 'Data Manager', 2 => 'Data Hotel', 3 => 'Paket Trial', 4 => 'TV Channels'] as $number => $label)
                    <button type="button" class="wizard-step {{ $number === 1 ? 'active' : '' }}" data-step-target="{{ $number }}">
                        <span>{{ $number }}</span><small>{{ $label }}</small>
                    </button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('platform.registrations.update', $registration) }}" id="registrationApprovalForm">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="confirmed">

            <div class="registration-wizard-panel active" data-step="1">
                <div class="card-body">
                    <h5 class="mb-1">Data calon manager hotel</h5>
                    <p class="text-muted">Akun berikut akan dibuat sebagai Manager Hotel dan diberi akses ke hotel yang disetujui.</p>
                    <div class="row">
                        @foreach ([
                            'Nama/Penanggung Jawab' => $registration->person_in_charge,
                            'Username' => $registration->username,
                            'Email' => $registration->email,
                            'Nomor WhatsApp' => $registration->whatsapp,
                            'Gender' => $registration->gender ? ucfirst($registration->gender) : '-',
                            'Alamat Manager' => $registration->manager_address ?: ($registration->admin_address ?: '-'),
                        ] as $label => $value)
                            <div class="col-md-6 mb-3">
                                <div class="manager-summary-item border rounded px-3 py-2">
                                    <small class="text-muted d-block mb-1">{{ $label }}</small>
                                    <strong>{{ $value }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="registration-wizard-panel" data-step="2">
                <div class="card-body">
                    <h5 class="mb-1">Konfirmasi data hotel</h5>
                    <p class="text-muted">Superadmin dapat memperbaiki nama atau alamat sebelum hotel dibuat.</p>
                    <div class="form-group">
                        <label for="hotel_name">Nama Hotel <span class="text-danger">*</span></label>
                        <input id="hotel_name" name="hotel_name" class="form-control @error('hotel_name') is-invalid @enderror" value="{{ old('hotel_name', $registration->hotel_name) }}" required maxlength="180">
                        @error('hotel_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="hotel_address">Alamat Hotel <span class="text-danger">*</span></label>
                        <textarea id="hotel_address" name="hotel_address" rows="5" class="form-control @error('hotel_address') is-invalid @enderror" required maxlength="2000">{{ old('hotel_address', $registration->hotel_address) }}</textarea>
                        @error('hotel_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="registration-wizard-panel" data-step="3">
                <div class="card-body">
                    <h5 class="mb-1">Paket aktivasi awal</h5>
                    <p class="text-muted">Pendaftaran baru selalu dimulai dengan paket trial berikut.</p>
                    <div class="trial-card border border-primary rounded p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:16px">
                            <div>
                                <span class="badge badge-primary mb-2">PAKET AWAL</span>
                                <h3 class="mb-2">{{ $trialPlan->nama }}</h3>
                                <p class="text-muted mb-0">{{ $trialPlan->deskripsi }}</p>
                            </div>
                            <div class="text-right">
                                <div><strong>{{ $trialPlan->durasi_hari ?: 'Tidak terbatas' }}</strong>{{ $trialPlan->durasi_hari ? ' hari' : '' }}</div>
                                <div><strong>{{ $trialPlan->maksimal_player ?: 'Tidak terbatas' }}</strong> player</div>
                                <div><strong>{{ $trialPlan->maksimal_user ?: 'Tidak terbatas' }}</strong> user hotel</div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0"><i class="fa fa-info-circle mr-1"></i>Manager tidak dihitung sebagai user hotel karena akunnya berada pada level portfolio.</div>
                </div>
            </div>

            <div class="registration-wizard-panel" data-step="4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:12px">
                        <div><h5 class="mb-1">TV channel paket {{ $trialPlan->nama }}</h5><p class="text-muted mb-0">Channel berikut ditentukan dari Master Paket dan otomatis aktif untuk hotel ini.</p></div>
                    </div>
                    <input id="channelSearch" class="form-control mb-3" placeholder="Cari nama, grup, tipe, atau wilayah channel">
                    @forelse ($channels->groupBy(fn ($channel) => $channel->group_title ?: 'Tanpa Kategori') as $group => $groupChannels)
                        <div class="channel-group mb-4">
                            <h6 class="border-bottom pb-2">{{ $group }}</h6>
                            <div class="row">
                                @foreach ($groupChannels as $channel)
                                    <div class="col-md-6 col-xl-4 channel-option" data-search="{{ Str::lower($channel->name.' '.$channel->slug.' '.$channel->type.' '.$channel->region.' '.$group) }}">
                                        <label class="border rounded p-3 d-flex align-items-center w-100" style="gap:10px;cursor:pointer">
                                            <i class="fa fa-check-circle text-success"></i>
                                            <span><strong class="d-block">{{ $channel->name }}</strong><small class="text-muted">{{ $group }} · {{ strtoupper($channel->type) }} · {{ ucfirst($channel->region) }}</small></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning">Paket default belum memiliki TV channel aktif. Atur channel melalui menu Master Paket.</div>
                    @endforelse

                    <div class="form-group mb-0">
                        <label for="admin_notes">Catatan Superadmin</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" class="form-control" maxlength="2000" placeholder="Catatan approval (opsional)">{{ old('admin_notes', $registration->admin_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('platform.registrations.index') }}" class="btn btn-light" id="cancelWizard">Batal</a>
                <div>
                    <button type="button" class="btn btn-outline-secondary d-none" id="previousStep"><i class="fa fa-arrow-left mr-1"></i>Sebelumnya</button>
                    <button type="button" class="btn btn-primary" id="nextStep">Selanjutnya<i class="fa fa-arrow-right ml-1"></i></button>
                    <button type="submit" class="btn btn-success d-none" id="approveRegistration" @disabled($channels->isEmpty())><i class="fa fa-check mr-1"></i>Approve &amp; Buat Hotel</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('css')
@parent
<style>
.wizard-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.wizard-step{border:0;background:transparent;color:#8992a6;padding:10px;text-align:center}.wizard-step span{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;margin:0 auto 6px;background:#e9edf4;font-weight:700}.wizard-step small{display:block;font-weight:600}.wizard-step.active,.wizard-step.completed{color:#3169df}.wizard-step.active span,.wizard-step.completed span{background:#3169df;color:#fff}.registration-wizard-panel{display:none;height:auto;min-height:0}.registration-wizard-panel.active{display:block}.manager-summary-item{height:auto;min-height:62px}.channel-option label{min-height:78px}.trial-card{background:linear-gradient(135deg,#f7faff,#fff)}
@media(max-width:767px){.wizard-steps{grid-template-columns:repeat(2,1fr)}.wizard-step small{font-size:11px}}
</style>
@endsection

@section('js')
<script>
$(function () {
    let currentStep = {{ $errors->has('hotel_name') || $errors->has('hotel_address') ? 2 : 1 }};
    const lastStep = 4;

    function showStep(step) {
        currentStep = Math.max(1, Math.min(lastStep, step));
        $('.registration-wizard-panel').removeClass('active').filter('[data-step="' + currentStep + '"]').addClass('active');
        $('.wizard-step').removeClass('active completed').each(function () {
            const number = Number($(this).data('step-target'));
            $(this).toggleClass('active', number === currentStep).toggleClass('completed', number < currentStep);
        });
        $('#previousStep').toggleClass('d-none', currentStep === 1);
        $('#nextStep').toggleClass('d-none', currentStep === lastStep);
        $('#approveRegistration').toggleClass('d-none', currentStep !== lastStep);
    }

    function currentStepIsValid() {
        if (currentStep !== 2) return true;
        const fields = document.querySelectorAll('[data-step="2"] [required]');
        for (const field of fields) {
            if (!field.reportValidity()) return false;
        }
        return true;
    }

    $('#nextStep').on('click', function () { if (currentStepIsValid()) showStep(currentStep + 1); });
    $('#previousStep').on('click', function () { showStep(currentStep - 1); });
    $('.wizard-step').on('click', function () {
        const target = Number($(this).data('step-target'));
        if (target < currentStep || currentStepIsValid()) showStep(target);
    });
    $('#channelSearch').on('input', function () {
        const term = $(this).val().toLowerCase();
        $('.channel-option').each(function () { $(this).toggle($(this).data('search').indexOf(term) !== -1); });
        $('.channel-group').each(function () { $(this).toggle($(this).find('.channel-option:visible').length > 0); });
    });
    $('#registrationApprovalForm').on('submit', function (event) {
        if (!confirm('Approve pendaftaran, buat hotel trial, dan aktifkan TV channel dari Master Paket?')) event.preventDefault();
    });
    showStep(currentStep);
});
</script>
@endsection
