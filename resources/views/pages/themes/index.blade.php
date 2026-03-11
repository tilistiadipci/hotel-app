@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.theme.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [['href' => '#', 'label' => trans('common.theme.title')]],
                ])
            </div>
        </div>

        <div class="row">
            @foreach ($themes as $theme)
                @php
                    $details = $theme->details->pluck('value', 'key');
                @endphp
                <div class="col-md-4">
                    <div class="main-card mb-3 card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">{{ $theme->name }}</h5>
                                    <small class="text-muted">{{ trans('common.theme.is_default') }}:
                                        {{ (string) ($theme->is_default ?? '0') === '1' ? trans('common.yes') : trans('common.no') }}</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    @if ((string) ($theme->is_default ?? '0') === '1')
                                        <button type="button" class="btn btn-success btn-sm mr-2" disabled>
                                            <i class="fa fa-check mr-1"></i>{{ trans('common.theme.default_active') }}
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-success btn-sm mr-2 btn-set-default"
                                            data-url="{{ route('themes.set-default', $theme->uuid) }}">
                                            <i class="fa fa-check-circle mr-1"></i>{{ trans('common.theme.set_default') }}
                                        </button>
                                    @endif
                                    <a href="{{ route('themes.edit', $theme->uuid) }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-edit mr-1"></i>{{ trans('common.edit') }}
                                    </a>
                                </div>
                            </div>

                            @if ($theme->imageMedia)
                                <div class="mb-3">
                                    <img src="{{ getMediaImageUrl($theme->imageMedia->storage_path, 320, 200) }}"
                                        alt="{{ $theme->name }}" class="img-fluid rounded shadow-sm"
                                        style="object-fit: cover;">
                                </div>
                            @else
                                <div class="mb-3">
                                    <img src="{{ getMediaImageUrl('default/theme-' . $theme->id . '.png', 320, 200) }}"
                                        alt="{{ $theme->name }}" class="img-fluid rounded shadow-sm"
                                        style="object-fit: cover;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-set-default').forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = this.dataset.url;
                    const currentButton = this;

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        beforeSend: function() {
                            currentButton.disabled = true;
                        },
                        success: function(res) {
                            swal({
                                title: 'Success',
                                text: res.message,
                                icon: 'success',
                            }).then(function() {
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            currentButton.disabled = false;
                            swal({
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    "{{ trans('common.error.500') }}",
                                icon: 'error',
                            });
                        }
                    });
                });
            });
        });
    </script>
@endsection
