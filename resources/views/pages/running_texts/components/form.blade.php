@php
    $runningText = $runningText ?? null;
    $isEdit = !empty($runningText);
@endphp

<form action="{{ $runningText ? route('running-texts.update', $runningText->uuid ?? $runningText->id) : route('running-texts.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($runningText)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-lg-6">
                <div class="border rounded p-3 mb-3">
                    <h6 class="mb-2">{{ trans('common.running_text.load_rss') }}</h6>
                    <div class="form-group">
                        <label for="rss_url">{{ trans('common.running_text.rss_url') }}</label>
                        <input type="url" id="rss_url" name="rss_url" class="form-control" placeholder="https://example.com/rss.xml"
                            value="{{ old('rss_url', ($runningText && $runningText->link_rss_type === 'link') ? $runningText->link_rss : '') }}">
                    </div>
                    <div class="form-group">
                        <label for="rss_file">{{ trans('common.running_text.rss_file') }}</label>
                        <input type="file" id="rss_file" name="rss_file" class="form-control-file"
                            accept=".xml,.rss,.txt,application/xml,text/xml">
                        @if (($runningText->link_rss_type ?? '') === 'uploaded' && !empty($runningText->link_rss))
                            <small class="text-muted d-block mt-1">
                                Saved file: {{ $runningText->link_rss }}
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnLoadRss">
                        <i class="fa fa-download mr-1"></i> {{ trans('common.running_text.load_rss') }}
                    </button>
                    <div class="text-muted small mt-2" id="rssHelpText">{{ trans('common.running_text.rss_help') }}</div>
                </div>

                <div class="border rounded p-3">
                    <h6 class="mb-2">{{ trans('common.running_text.rss_items') }}</h6>
                    <div class="d-flex align-items-center mb-2">
                        <label class="custom-checkbox mb-0">
                            <input type="checkbox" id="rssCheckAll">
                            <span class="checkmark"></span>
                        </label>
                        <small class="text-muted ml-2">{{ trans('common.running_text.check_all') }}</small>
                    </div>
                    <div id="rssItemsLoading" class="text-center text-muted py-3 d-none">
                        <i class="fa fa-spinner fa-spin mr-1"></i> {{ trans('common.running_text.loading_rss') }}
                    </div>
                    <div id="rssItemsEmpty" class="text-center text-muted py-3">
                        {{ trans('common.running_text.rss_empty') }}
                    </div>
                    <div id="rssItemsList" class="list-group" style="max-height: 350px; overflow-y: auto;"></div>
                </div>
            </div>

            <div class="col-lg-6">
                @if ($isEdit)
                    <div class="position-relative row form-group">
                        <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.title') }}</label>
                        <div class="col-sm-9">
                            @include('partials.forms.input', [
                                'elementId' => 'title',
                                'required' => true,
                                'value' => $runningText->title ?? old('title'),
                                'type' => 'text',
                                'maxlength' => 200,
                            ])
                        </div>
                    </div>

                    <div class="position-relative row form-group">
                        <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.description') }}</label>
                        <div class="col-sm-9">
                            <textarea name="description" id="description" class="form-control" rows="5">{{ $runningText->description ?? old('description') }}</textarea>
                        </div>
                    </div>

                    <div class="position-relative row form-group">
                        <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                        <div class="col-sm-9">
                            @include('partials.forms.input', [
                                'elementId' => 'sort_order',
                                'value' => $runningText->sort_order ?? old('sort_order', 0),
                                'type' => 'number',
                                'min' => 0,
                            ])
                        </div>
                    </div>

                    <div class="position-relative row form-group">
                        <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                        <div class="col-sm-9">
                            @php
                                $isActive = $runningText->is_active ?? old('is_active', 1);
                            @endphp
                            <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                                <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                                <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                            </select>
                        </div>
                    </div>
                @else
                    <div class="border rounded p-3">
                        <h6 class="mb-2">{{ trans('common.running_text.selected_items') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="selectedRssTable">
                                <thead>
                                    <tr>
                                        <th style="width: 45%">{{ trans('common.title') }}</th>
                                        <th style="width: 20%">{{ trans('common.sort_order') }}</th>
                                        <th style="width: 20%">{{ trans('common.status') }}</th>
                                        <th style="width: 15%">{{ trans('common.action2') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="selectedEmptyRow">
                                        <td colspan="4" class="text-center text-muted">{{ trans('common.running_text.no_selected') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('running-texts.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@section('js')
    @parent
    <script>
        (function() {
            const previewUrl = "{{ route('running-texts.preview-rss') }}";
            const $btnLoad = $('#btnLoadRss');
            const $rssUrl = $('#rss_url');
            const $rssFile = $('#rss_file');
            const $list = $('#rssItemsList');
            const $empty = $('#rssItemsEmpty');
            const $loading = $('#rssItemsLoading');
            const $checkAll = $('#rssCheckAll');
            const $selectedTable = $('#selectedRssTable tbody');
            const selectedMap = new Map();

            function renderItems(items) {
                $list.empty();
                if (!items || !items.length) {
                    $empty.removeClass('d-none');
                    return;
                }
                $empty.addClass('d-none');
                items.forEach((item, idx) => {
                    const title = item.title || '(No title)';
                    const desc = item.description || '';
                    const snippet = desc.length > 160 ? desc.substring(0, 160) + '...' : desc;
                    const safeTitle = $('<div>').text(title).html();
                    const safeDesc = $('<div>').text(desc).html();
                    const rawTitle = encodeURIComponent(title);
                    const rawDesc = encodeURIComponent(desc);
                    const html = `
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <label class="custom-checkbox mt-1 mb-0 mr-2">
                                    <input type="checkbox" class="rss-item-check" data-raw-title="${rawTitle}" data-raw-description="${rawDesc}">
                                    <span class="checkmark"></span>
                                </label>
                                <div>
                                    <div class="font-weight-bold mb-1">${safeTitle}</div>
                                    <div class="text-muted small">${$('<div>').text(snippet).html()}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    $list.append(html);
                });
            }

            $btnLoad.on('click', function() {
                const url = ($rssUrl.val() || '').trim();
                const file = $rssFile[0] && $rssFile[0].files ? $rssFile[0].files[0] : null;
                if (!url && !file) {
                    toastr["warning"]('{{ trans('common.running_text.rss_required') }}', 'Warning');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', "{{ csrf_token() }}");
                if (url) formData.append('rss_url', url);
                if (file) formData.append('rss_file', file);

                $loading.removeClass('d-none');
                $empty.addClass('d-none');
                $list.empty();

                $.ajax({
                    url: previewUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res && res.status) {
                            renderItems(res.items || []);
                        } else {
                            $empty.removeClass('d-none').text(res.message || '{{ trans('common.running_text.rss_invalid') }}');
                        }
                    },
                    error: function() {
                        $empty.removeClass('d-none').text('{{ trans('common.running_text.rss_failed') }}');
                    },
                    complete: function() {
                        $loading.addClass('d-none');
                    }
                });
            });

            $checkAll.on('change', function() {
                const checked = this.checked;
                $list.find('.rss-item-check').each(function() {
                    $(this).prop('checked', checked).trigger('change');
                });
            });

            $list.on('change', '.rss-item-check', function() {
                const rawTitle = $(this).data('raw-title') || '';
                const rawDescription = $(this).data('raw-description') || '';
                const title = decodeURIComponent(rawTitle);
                const description = decodeURIComponent(rawDescription);
                const key = `${title}::${description}`;

                if (this.checked) {
                    if (!selectedMap.has(key)) {
                        selectedMap.set(key, { title, description });
                        addSelectedRow(key, title, description);
                    }
                } else {
                    removeSelectedRow(key);
                }

                const total = $list.find('.rss-item-check').length;
                const checkedCount = $list.find('.rss-item-check:checked').length;
                $checkAll.prop('checked', total > 0 && total === checkedCount);
            });

            function addSelectedRow(key, title, description) {
                $('#selectedEmptyRow').remove();
                const rowId = `selected-${btoa(unescape(encodeURIComponent(key))).replace(/=/g, '')}`;
                const row = `
                    <tr data-key="${key}" id="${rowId}">
                        <td>
                            ${title}
                            <input type="hidden" name="titles[]" value="${title}">
                            <input type="hidden" name="descriptions[]" value="${description}">
                        </td>
                        <td>
                            <input type="number" name="sort_orders[]" class="form-control form-control-sm" value="${selectedMap.size - 1}" min="0">
                        </td>
                        <td>
                            <select name="is_actives[]" class="form-control form-control-sm">
                                <option value="1" selected>{{ trans('common.active') }}</option>
                                <option value="0">{{ trans('common.inactive') }}</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-selected" data-key="${key}">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $selectedTable.append(row);
            }

            function removeSelectedRow(key) {
                selectedMap.delete(key);
                $selectedTable.find(`tr[data-key="${key}"]`).remove();
                if ($selectedTable.find('tr').length === 0) {
                    $selectedTable.append('<tr id="selectedEmptyRow"><td colspan="4" class="text-center text-muted">{{ trans('common.running_text.no_selected') }}</td></tr>');
                }
            }

            $selectedTable.on('click', '.btn-remove-selected', function() {
                const key = $(this).data('key');
                removeSelectedRow(key);
                $list.find(`.rss-item-check`).each(function() {
                    const rawTitle = $(this).data('raw-title') || '';
                    const rawDescription = $(this).data('raw-description') || '';
                    const t = decodeURIComponent(rawTitle);
                    const d = decodeURIComponent(rawDescription);
                    if (`${t}::${d}` === key) {
                        $(this).prop('checked', false);
                    }
                });
                const total = $list.find('.rss-item-check').length;
                const checkedCount = $list.find('.rss-item-check:checked').length;
                $checkAll.prop('checked', total > 0 && total === checkedCount);
            });

            (function waitForjQuery() {
                if (window.jQuery && {{ $isEdit ? 'true' : 'false' }}) {
                    const el = $('#is_active');
                    if (el.hasClass('select2-hidden-accessible')) {
                        el.select2('destroy');
                    }
                    el.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: "{{ trans('common.select_an_option') ?? 'Select an option' }}"
                    });
                } else {
                    setTimeout(waitForjQuery, 50);
                }
            })();
        })();
    </script>
@endsection
