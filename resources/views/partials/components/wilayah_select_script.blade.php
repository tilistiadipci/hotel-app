<script>
(function ($) {
    $('.js-wilayah-select').each(function () {
        const $select = $(this);
        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: $select.data('placeholder'),
            minimumInputLength: 2,
            ajax: {
                url: @json(route('wilayah-indonesia.search')),
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (response) {
                    return { results: response.results || [] };
                },
                cache: true
            },
            language: {
                inputTooShort: function () { return 'Ketik minimal 2 karakter'; },
                noResults: function () { return 'Wilayah tidak ditemukan'; },
                searching: function () { return 'Mencari wilayah...'; }
            }
        });
    });
})(jQuery);
</script>
