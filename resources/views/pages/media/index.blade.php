@extends('templates.index')

@section('css')
    <style>
        .media-hero {
            display: flex;
            gap: 18px;
        }
        .media-list {
            flex: 2;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .media-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            transition: background 0.15s ease;
            cursor: pointer;
            background: #fff;
            border: 1px solid #e5e7eb;
        }
        .media-item:hover { background: #eef2ff; }
        .media-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }
        .media-meta {
            color: #6b7280;
            font-size: 12px;
        }
        .media-upload {
            flex: 1;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 18px;
            background: #fff;
        }
        .dropzone {
            border: 1px dashed #94a3b8;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
        }
        .dropzone:hover { border-color: #3b82f6; }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e5e7eb;
            font-size: 12px;
            color: #374151;
        }
        .usage-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 12px;
        }
    </style>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Media Library',
                    'icon' => $icon ?? 'fa fa-photo-film',
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => 'Media Library'],
                    ],
                ])
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="mediaTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-media-tab="image">Image</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="video">Video</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="audio">Audio</a></li>
                </ul>

                <div class="media-hero">
                    <div class="media-list" id="mediaList">
                        <div class="text-muted mb-2 small" id="mediaCount"></div>
                        <div id="mediaItems"></div>
                    </div>
                    <div class="media-upload">
                        <h6 class="mb-2 font-weight-bold">Upload New Media</h6>
                        <div class="dropzone mb-3" id="dropzone">
                            <div><i class="fa fa-cloud-upload fa-2x text-primary mb-2"></i></div>
                            <div class="text-muted small">Drop your file here or click to browse</div>
                            <input type="file" class="d-none" id="uploadInput" accept="image/*">
                        </div>
                        <form id="uploadForm">
                            @csrf
                            <input type="hidden" name="type" id="uploadType" value="image">
                            <input type="hidden" name="duration" id="uploadDuration" value="">
                            <div class="form-group">
                                <label class="small text-muted mb-1">Media Name</label>
                                <input type="text" class="form-control" name="name" id="uploadName" placeholder="Optional">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" id="uploadBtn">
                                <i class="fa fa-upload mr-1"></i> Complete Upload
                            </button>
                        </form>

                        <div class="usage-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">Storage Used</span>
                                <span class="small font-weight-bold">
                                    {{ $usageHuman }}{{ $quotaHuman ? ' / ' . $quotaHuman : '' }}
                                </span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                @php
                                    $pct = $usagePercent ?? 0;
                                @endphp
                                @php
                                    $barPct = $pct > 0 ? max($pct, 0.1) : 0;
                                @endphp
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $barPct }}%;" aria-valuenow="{{ $barPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            @if($quotaHuman)
                                <div class="small text-muted mt-1">{{ $usagePercentLabel ?? ($usagePercent ? round($usagePercent, 1) . '%' : '0%') }} of quota</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const datasets = {
            image: { items: @json($images), next: @json($nextImage), loading: false },
            video: { items: @json($videos), next: @json($nextVideo), loading: false },
            audio: { items: @json($audios), next: @json($nextAudio), loading: false },
        };

        function humanSize(bytes) {
            if (!bytes) return '';
            const kb = bytes / 1024;
            if (kb < 1024) return kb.toFixed(1) + ' KB';
            return (kb / 1024).toFixed(2) + ' MB';
        }

        function detectDuration(file) {
            return new Promise((resolve) => {
                if (!file || (!file.type.startsWith('video/') && !file.type.startsWith('audio/'))) {
                    return resolve(null);
                }
                const el = file.type.startsWith('video/') ? document.createElement('video') : document.createElement('audio');
                el.preload = 'metadata';
                const url = URL.createObjectURL(file);
                el.src = url;
                el.onloadedmetadata = function() {
                    if (el.duration && isFinite(el.duration)) {
                        resolve(Math.round(el.duration));
                    } else {
                        resolve(null);
                    }
                    URL.revokeObjectURL(url);
                };
                el.onerror = function() {
                    resolve(null);
                    URL.revokeObjectURL(url);
                };
            });
        }

        function renderList(type) {
            const bucket = datasets[type];
            const items = bucket.items || [];
            const wrap = $('#mediaItems');
            wrap.empty();
            $('#mediaCount').text(items.length + ' items total');
            items.forEach(item => {
                const thumb = item.type === 'image' ? (item.thumb_url || '') : '';
                const icon = item.type === 'video' ? 'fa-film' : 'fa-music';
                const meta = [];
                meta.push((item.extension || '').toUpperCase());
                if (item.size) meta.push(humanSize(item.size));
                if (item.type === 'image' && item.width && item.height) meta.push(item.width + 'x' + item.height);
                if (item.type === 'video' && item.duration) meta.push(item.duration + 's');

                wrap.append(`
                    <div class="media-item">
                        ${item.type === 'image'
                            ? `<img class="media-thumb" src="${thumb}" alt="${item.name}">`
                            : `<div class="media-thumb d-flex align-items-center justify-content-center"><i class="fa ${icon} text-primary"></i></div>`}
                        <div>
                            ${item.name === item.original_filename ? `` : `<div class="font-weight-semibold">${item.name}</div>`}
                            <div class="font-weight-semibold">${item.original_filename}</div>
                            <div class="media-meta">${meta.join(' • ')}</div>
                        </div>
                    </div>
                `);
            });
            if (!items.length) {
                wrap.html('<div class="text-muted small">Belum ada media untuk kategori ini.</div>');
            }
        }

        function setTab(type) {
            $('#mediaTabs .nav-link').removeClass('active');
            $(`#mediaTabs [data-media-tab="${type}"]`).addClass('active');
            $('#uploadType').val(type);
            if (type === 'image') $('#uploadInput').attr('accept', 'image/*');
            else if (type === 'video') $('#uploadInput').attr('accept', 'video/*');
            else $('#uploadInput').attr('accept', 'audio/*');
            renderList(type);
        }

        function loadMore(type) {
            const bucket = datasets[type];
            if (!bucket.next || bucket.loading) return;
            bucket.loading = true;
            $.get(bucket.next, function(res) {
                if (res.status) {
                    bucket.items = bucket.items.concat(res.items);
                    bucket.next = res.next_url;
                    renderList(type);
                }
            }).always(function() {
                bucket.loading = false;
            });
        }

        $(function () {
            $('#mediaTabs [data-media-tab]').on('click', function(e) {
                e.preventDefault();
                setTab($(this).data('media-tab'));
            });

            setTab('image');

            $('#mediaList').on('scroll', function() {
                const type = $('#mediaTabs .nav-link.active').data('media-tab');
                const el = this;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 50) {
                    loadMore(type);
                }
            });

            const dz = $('#dropzone');
            const input = $('#uploadInput');
            dz.on('click', () => input.trigger('click'));
            input.on('click', function(e) { e.stopPropagation(); });

            input.on('change', function() {
                const file = this.files[0];
                if (file) {
                    $('#uploadName').val(file.name);
                    detectDuration(file).then((secs) => {
                        $('#uploadDuration').val(secs ?? '');
                    });
                }
            });

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                const file = input[0].files[0];
                if (!file) {
                    alert('Pilih file terlebih dahulu.');
                    return;
                }
                const formData = new FormData(this);
                formData.append('file', file);
                $.ajax({
                    url: "{{ route('media.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#uploadBtn').prop('disabled', true).text('Uploading...');
                    },
                    success: function(res) {
                        if (res.status && res.media) {
                            const type = res.media.type;
                            datasets[type].items.unshift(res.media);
                            renderList(type);
                            input.val('');
                            $('#uploadName').val('');
                            toastr["success"]("{{ trans('common.success.create') }}", "Success");
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr)
                        toastr["error"]("{{ trans('common.error.500') }}", "Error");
                    },
                    complete: function() {
                        swal.close();
                        $('#uploadBtn').prop('disabled', false).html('<i class="fa fa-upload mr-1"></i> Complete Upload');
                    }
                });
            });
        });
    </script>
@endsection
