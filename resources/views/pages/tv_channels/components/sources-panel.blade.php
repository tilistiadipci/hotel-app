@php
    $sources = $channel->sources ?? collect();
@endphp

<div class="card mb-3">
    <div class="card-header-tab card-header">
        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
            {{ __('platform.tv_catalog.sources_panel_title') }}
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ __('platform.tv_catalog.sources_panel_help') }}</p>

        @if ($sources->isEmpty())
            <p class="text-muted">{{ __('platform.tv_catalog.sources_empty') }}</p>
        @else
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="width:160px">{{ __('platform.tv_catalog.source_label') }}</th>
                            <th>Stream URL</th>
                            <th style="width:80px" class="text-center">{!! trans('common.action') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sources as $source)
                            <tr>
                                <td>{{ $source->label ?: '-' }}</td>
                                <td><code>{{ Str::limit($source->stream_url, 90) }}</code></td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('tv-channels.sources.destroy', [$channel->uuid, $source->id]) }}" onsubmit="return confirm('{{ __('common.are_you_sure') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('tv-channels.sources.store', $channel->uuid) }}" class="form-row align-items-end">
            @csrf
            <div class="col-md-3 form-group mb-0">
                <label>{{ __('platform.tv_catalog.source_label') }}</label>
                <input type="text" name="label" class="form-control" maxlength="100" placeholder="{{ __('platform.tv_catalog.source_label_placeholder') }}">
            </div>
            <div class="col-md-7 form-group mb-0">
                <label>Stream URL</label>
                <input type="text" name="stream_url" class="form-control" required>
            </div>
            <div class="col-md-2 form-group mb-0">
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus mr-1"></i>{{ __('platform.tv_catalog.sources_add_label') }}</button>
            </div>
        </form>
    </div>
</div>
