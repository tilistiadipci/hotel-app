<script type="text/javascript">
    var bulkUrl = "{{ url()->current() }}/bulkDelete";

    $('#applyBulkAction').click(function() {
        const selectedRows = $('.data-check:checked');

        if (selectedRows.length == 0) {
            swal({
                title: "{{ trans('common.choose_item') }}",
                text: "{{ trans('common.choose_item_text') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
        } else {
            swal({
                title: "{{ trans('common.are_you_sure') }}",
                text: "{{ trans('common.delete_confirm_text') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).
            then((willDelete) => {
                if (willDelete) {
                    let uids = [];
                    table.column('checkbox:name').nodes().to$().find('.data-check:checked').each(function() {
                        uids.push($(this).val());
                    });

                    applyBulkAction(uids);
                }
            })
        }
    });

    function applyBulkAction(uids) {
        if (!uids || uids.length === 0) return;

        $.ajax({
            url: bulkUrl,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                uids: uids,
            },
            success: function(data) {
                if (data.status) {
                    toastr["success"](data.message, "Success");
                    table.ajax.reload(null, false);
                } else {
                    toastr["error"](data.message, "Warning");
                }
            }
        });
    }
</script>
