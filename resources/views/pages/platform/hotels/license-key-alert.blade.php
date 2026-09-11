@if (session('plain_license_key'))
    <div class="alert alert-warning shadow-sm">
        <div class="d-flex align-items-start">
            <i class="fa fa-key fa-lg mr-3 mt-1"></i>
            <div class="flex-grow-1">
                <strong>Salin license key ini sekarang.</strong>
                <p class="mb-2 small">
                    Nilai asli tidak disimpan dan tidak dapat ditampilkan kembali. Kirim sebagai header
                    <code>X-Hotel-License</code>, bersama kode hotel pada header <code>X-Hotel-Code</code>.
                </p>
                <div class="input-group">
                    <input id="generatedLicenseKey" type="text" class="form-control font-weight-bold"
                        value="{{ session('plain_license_key') }}" readonly>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-dark"
                            onclick="navigator.clipboard.writeText(document.getElementById('generatedLicenseKey').value); this.innerHTML='<i class=&quot;fa fa-check mr-1&quot;></i>Tersalin';">
                            <i class="fa fa-copy mr-1"></i>Salin
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
