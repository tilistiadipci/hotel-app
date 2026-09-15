@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Landing Page', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Website Platform']]])
        <div class="page-title-actions">
            <a href="{{ route('landing.show') }}" target="_blank" class="btn btn-outline-primary"><i class="fa fa-external-link-alt mr-1"></i>Lihat Landing Page</a>
        </div>
    </div></div>

    <div class="row">
        @foreach ([['Total Kunjungan', $stats['total'], 'fa-eye', 'primary'], ['IP Unik', $stats['unique'], 'fa-network-wired', 'info'], ['Hari Ini', $stats['today'], 'fa-calendar-day', 'success'], ['Bulan Ini', $stats['month'], 'fa-calendar-alt', 'warning']] as [$label, $value, $itemIcon, $color])
            <div class="col-md-6 col-xl-3"><div class="card mb-3 widget-content bg-{{ $color }} text-white"><div class="widget-content-wrapper">
                <div class="widget-content-left"><div class="widget-heading"><i class="fa {{ $itemIcon }} mr-1"></i>{{ $label }}</div></div>
                <div class="widget-content-right"><div class="widget-numbers">{{ number_format($value) }}</div></div>
            </div></div></div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><i class="fa fa-edit mr-2"></i>Konten dan SEO Landing Page</div>
                <form method="POST" action="{{ route('platform.landing-page.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-info">
                            Tempelkan HTML untuk isi halaman di textarea. Gunakan <code>@{{LOGIN_URL}}</code> untuk URL login, <code>@{{REGISTER_URL}}</code> untuk URL pendaftaran hotel, <code>@{{CSRF_FIELD}}</code> di dalam form login, <code>@{{LOGO_URL}}</code> untuk logo, <code>@{{SITE_NAME}}</code> untuk nama website, dan <code>@{{BASE_URL}}</code> untuk base URL aplikasi. HTML ini hanya boleh dikelola oleh superadmin tepercaya karena akan ditampilkan apa adanya.
                        </div>

                        <div class="alert alert-light border">
                            Form login buatan sendiri harus memakai <code>method="POST"</code>, <code>action="@{{LOGIN_URL}}"</code>, menyertakan <code>@{{CSRF_FIELD}}</code>, serta input bernama <code>text</code> dan <code>password</code>. Jika hanya membutuhkan tombol menuju halaman login bawaan, cukup gunakan tautan <code>href="@{{LOGIN_URL}}"</code>.
                        </div>

                        <div class="position-relative row form-group">
                            <label for="site_name" class="col-sm-3 col-form-label text-sm-right">Nama Website</label>
                            <div class="col-sm-9"><input id="site_name" name="site_name" class="form-control @error('site_name') is-invalid @enderror" value="{{ old('site_name', $landingPage->site_name) }}">@error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="position-relative row form-group">
                            <label for="meta_title" class="col-sm-3 col-form-label text-sm-right">Meta Title</label>
                            <div class="col-sm-9"><input id="meta_title" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $landingPage->meta_title) }}"><small class="text-muted">Judul yang tampil pada tab browser dan hasil pencarian.</small>@error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="position-relative row form-group">
                            <label for="meta_description" class="col-sm-3 col-form-label text-sm-right">Meta Description</label>
                            <div class="col-sm-9"><textarea id="meta_description" name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $landingPage->meta_description) }}</textarea><small class="text-muted">Ringkasan website untuk mesin pencari dan saat tautan dibagikan.</small>@error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="position-relative row form-group">
                            <label for="meta_keywords" class="col-sm-3 col-form-label text-sm-right">Kata Kunci SEO</label>
                            <div class="col-sm-9"><input id="meta_keywords" name="meta_keywords" class="form-control @error('meta_keywords') is-invalid @enderror" value="{{ old('meta_keywords', $landingPage->meta_keywords) }}"><small class="text-muted">Pisahkan setiap kata kunci dengan koma.</small>@error('meta_keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="position-relative row form-group">
                            <label for="logo" class="col-sm-3 col-form-label text-sm-right">Logo</label>
                            <div class="col-sm-9">
                                @if($landingPage->logo_path)<img src="{{ asset('storage/'.$landingPage->logo_path) }}" alt="Logo landing page" class="d-block mb-2" style="max-width:220px;max-height:100px">@endif
                                <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control-file @error('logo') is-invalid @enderror">
                                <small class="text-muted">JPG, PNG, atau WebP maksimal 2 MB. Pakai URL-nya di HTML melalui <code>@{{LOGO_URL}}</code>.</small>
                                @if($landingPage->logo_path)<div class="custom-control custom-checkbox mt-2"><input class="custom-control-input" id="remove_logo" name="remove_logo" type="checkbox" value="1"><label class="custom-control-label" for="remove_logo">Hapus logo saat ini</label></div>@endif
                                @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="position-relative row form-group">
                            <label for="is_active" class="col-sm-3 col-form-label text-sm-right">Status Publikasi</label>
                            <div class="col-sm-9"><select id="is_active" name="is_active" class="form-control"><option value="1" @selected((bool) old('is_active', $landingPage->is_active))>Aktif — tampil di base URL</option><option value="0" @selected(! (bool) old('is_active', $landingPage->is_active))>Nonaktif — arahkan ke login/dashboard</option></select></div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="html_content"><strong>HTML Landing Page</strong></label>
                            <textarea id="html_content" name="html_content" rows="28" spellcheck="false" class="form-control font-monospace @error('html_content') is-invalid @enderror" style="font-family:Consolas,Monaco,monospace;font-size:13px;line-height:1.55">{{ old('html_content', $landingPage->html_content) }}</textarea>
                            @error('html_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer d-block text-right"><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan Landing Page</button></div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fa fa-users mr-2"></i>Pengunjung Terbaru</div>
                <div class="card-body table-responsive p-0"><table class="table table-striped mb-0"><thead><tr><th>IP Address</th><th>Total</th><th>Terakhir</th></tr></thead><tbody>
                    @forelse($recentVisitors as $visitor)<tr><td><code>{{ $visitor->ip_address }}</code></td><td>{{ number_format($visitor->total) }}</td><td>{{ \Carbon\Carbon::parse($visitor->last_visit)->format('d/m/Y H:i') }}</td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada kunjungan.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>
@endsection
