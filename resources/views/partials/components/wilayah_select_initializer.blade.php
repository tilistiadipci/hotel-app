if (typeof window.initializeWilayahSelect !== 'function') {
    window.initializeWilayahSelect = function(root) {
        const $root = root ? $(root) : $(document);
        let $selects = $root.find('.js-wilayah-select');

        if ($root.is && $root.is('.js-wilayah-select')) {
            $selects = $selects.add($root);
        }

        $selects.each(function() {
            const $select = $(this);
            const searchUrl = $select.attr('data-wilayah-search-url');

            if (!searchUrl || !$.fn.select2) {
                return;
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            // Bersihkan container yatim/duplikat dari inisialisasi Select2 sebelumnya.
            $select.siblings('span.select2-container').remove();

            $select.select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: $select.data('placeholder'),
                minimumInputLength: 2,
                ajax: {
                    url: searchUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { q: params.term || '' };
                    },
                    processResults: function(response) {
                        return { results: response.results || [] };
                    },
                    cache: true
                },
                language: {
                    inputTooShort: function() { return 'Ketik minimal 2 karakter'; },
                    noResults: function() { return 'Wilayah tidak ditemukan'; },
                    searching: function() { return 'Mencari wilayah...'; },
                    errorLoading: function() { return 'Wilayah gagal dimuat'; }
                }
            });
        });
    };
}
