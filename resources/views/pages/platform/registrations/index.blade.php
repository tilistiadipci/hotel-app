@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title"><div class="page-title-wrapper">
            @include('templates.parts.breadcrumb', ['title' => __('platform.registration.admin_title'), 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => __('platform.registration.admin_title')]]])
        </div></div>

        <div class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center justify-content-between" style="gap:12px">
            <div><h5 class="mb-1">{{ __('platform.registration.admin_title') }}</h5><div class="text-muted">{{ trans_choice('platform.registration.pending_count', $pendingCount, ['count' => $pendingCount]) }}</div></div>
            <form method="GET" class="form-inline"><select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">{{ __('platform.registration.all_statuses') }}</option>
                @foreach (['pending', 'confirmed', 'rejected'] as $filterStatus)<option value="{{ $filterStatus }}" @selected($status === $filterStatus)>{{ __('platform.registration.status_'.$filterStatus) }}</option>@endforeach
            </select></form>
        </div></div>

        @if ($registrations->isEmpty())
            <div class="card"><div class="card-body text-center py-5"><div class="mb-3"><i class="fa fa-clipboard-list fa-3x text-primary"></i></div><h5>{{ __('platform.registration.empty_title') }}</h5><p class="text-muted mb-0">{{ __('platform.registration.empty_description') }}</p></div></div>
        @else
            <div class="card"><div class="card-body table-responsive">
                <table class="table table-striped table-bordered mb-0"><thead><tr>
                    <th>{{ __('platform.registration.hotel_name') }}</th><th>{{ __('platform.registration.person_in_charge') }}</th><th>{{ __('platform.registration.contact') }}</th><th>{{ __('platform.registration.registered_at') }}</th><th>{{ __('platform.registration.status') }}</th><th style="min-width:270px">{{ __('platform.registration.action') }}</th>
                </tr></thead><tbody>
                @foreach ($registrations as $registration)
                    @php($badge = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'][$registration->status] ?? 'secondary')
                    <tr>
                        <td><strong>{{ $registration->hotel_name }}</strong><small class="d-block text-muted">{{ Str::limit($registration->hotel_address, 70) }}</small></td>
                        <td>{{ $registration->person_in_charge }}<small class="d-block text-muted"><i class="fa fa-user mr-1"></i>{{ $registration->username ?: '-' }}</small></td>
                        <td><a href="mailto:{{ $registration->email }}">{{ $registration->email }}</a><br><a href="https://wa.me/{{ ltrim($registration->whatsapp, '+') }}" target="_blank" rel="noopener">{{ $registration->whatsapp }}</a></td>
                        <td>{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge badge-{{ $badge }}">{{ __('platform.registration.status_'.$registration->status) }}</span>@if ($registration->reviewed_at)<small class="d-block text-muted mt-1">{{ $registration->reviewed_at->format('d/m/Y H:i') }}</small>@endif</td>
                        <td>@if($registration->status === \App\Models\Registration::STATUS_PENDING)<form method="POST" action="{{ route('platform.registrations.update', $registration) }}" class="registration-review-form">
                            @csrf @method('PATCH')
                            <textarea name="admin_notes" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('platform.registration.notes_placeholder') }}">{{ $registration->admin_notes }}</textarea>
                            <button name="status" value="confirmed" class="btn btn-success btn-sm"><i class="fa fa-check mr-1"></i>{{ __('platform.registration.confirm') }}</button>
                            <button name="status" value="rejected" class="btn btn-outline-danger btn-sm"><i class="fa fa-times mr-1"></i>{{ __('platform.registration.reject') }}</button>
                        </form>@else
                            @if($registration->admin_notes)<div class="small text-muted mb-2">{{ $registration->admin_notes }}</div>@endif
                            @if($registration->hotel)<a class="btn btn-outline-primary btn-sm" href="{{ route('platform.hotels.show', $registration->hotel) }}"><i class="fa fa-hotel mr-1"></i>Lihat Hotel</a>@endif
                            @if($registration->adminUser)<a class="btn btn-outline-secondary btn-sm" href="{{ route('platform.hotel-admins.edit', $registration->adminUser) }}"><i class="fa fa-user-shield mr-1"></i>Lihat Akun</a>@endif
                        @endif</td>
                    </tr>
                @endforeach
                </tbody></table>
            </div><div class="card-footer">{{ $registrations->links() }}</div></div>
        @endif
    </div>
@endsection

@section('js')
<script>
$(document).on('submit','.registration-review-form',function(event){
    const submitter=event.originalEvent&&event.originalEvent.submitter;
    const message=submitter&&submitter.value==='confirmed'?@json(__('platform.registration.confirm_prompt')):@json(__('platform.registration.reject_prompt'));
    if(!confirm(message)) event.preventDefault();
});
</script>
@endsection
