<script type="text/javascript" defer>
    var table;
    $(document).ready(function() {

        // Ambil URL dan cek apakah ada parameter 'asset_name'
        const urlParams = new URLSearchParams(window.location.search);
        const assetName = urlParams.get('asset_name');

        if (typeof orders === 'undefined') {
            orders = [
                [columns.length - 1, 'desc']
            ];
        }

        if (typeof fixedColumns === 'undefined') {
            fixedColumns = {
                leftColumns: 1,
                rightColumns: 2,
            }
        }

        if (typeof ajax === 'undefined') {
            ajax = {
                url: getUrl,
                data: function(d) {
                    if (typeof attachFilters === 'function') {
                        attachFilters(d);
                    }
                }
            };
        }

        table = $('.data-table').DataTable({
            scrollY: "auto",
            scrollX: (typeof scrollX !== 'undefined') ? scrollX : true,
            scrollCollapse: true,
            searching: typeof searching !== 'undefined' ? searching : true,
            fixedColumns: fixedColumns || false,
            processing: true,
            serverSide: true,
            lengthMenu: [10, 20, 50, 100, 200],
            // responsive: true,
            deferRender: true,
            ajax: ajax,
            columns: columns || [],
            order: orders,
            drawCallback: function() {
                const $tips = $('.data-table [data-toggle="tooltip"]');
                $tips.tooltip('dispose');
                $tips.tooltip({ container: 'body' });
            }
        });

        // Jika ada parameter 'asset_name', lakukan pencarian otomatis
        if (assetName) {
            table.search(assetName).draw();
        }

        // tooltips
        $('[data-toggle=\"tooltip\"]').tooltip();

        // filter button opens sidebar
        $('#filterBtn').on('click', function() {
            toggleFilter(true);
        });

        // reset filter button clears search and reloads
        $('#resetFilterBtn').on('click', function() {
            resetFilters();
        });

        // let DataTables_Table_0_length = $('#DataTables_Table_0_length').find('select').val();
        // console.log(DataTables_Table_0_length);

        // $('#DataTables_Table_0_length').find('select').on('change', function() {
        //     console.log($(this).val())
        // })

        // $('#DataTables_Table_0_filter').find('input').on('keyup', function() {
        //     console.log($(this).val())
        // })

        // $('#DataTables_Table_0_paginate').find('a').on('click', function() {
        //     console.log($(this).text())
        // })
    })

    // Handle check all click
    $('#checkAll').on('click', function() {
        var checked = this.checked;
        $('.data-check').prop('checked', checked);
    });

    $('.data-table').on('click', '.data-check', function() {
        // Total checkbox dalam DataTable
        var totalCheckboxesDataTable = table.column('checkbox:name').nodes().length;

        // Total checkbox yang dicentang dalam DataTable
        var checkedCheckboxesDataTable = table.column('checkbox:name').nodes().to$().find(
            'input[type="checkbox"]:checked').length;

        // Total checkbox dengan class .data-check (termasuk di luar DataTable)
        var totalCheckboxesClassDataCheck = $('.data-check').length;

        // Total checkbox dengan class .data-check yang dicentang (termasuk di luar DataTable)
        var totalCheckedClassDataCheck = $('.data-check:checked').length;


        let totalChecked = totalCheckedClassDataCheck - totalCheckboxesDataTable;

        // console.log(totalCheckboxesDataTable, checkedCheckboxesDataTable, totalCheckboxesClassDataCheck,
        //     totalCheckedClassDataCheck, totalChecked);

        if (totalCheckboxesDataTable === totalChecked) {
            $('.data-table thead').find('#checkAll').prop('checked', true);
        } else {
            $('.data-table thead').find('#checkAll').prop('checked', false);
        }
    });

    function show(id) {
        $('#showModal').modal('show');
        $.ajax({
            url: showUrl.replace(':id', id),
            type: 'GET',
            beforeSend: function() {
                $('#showModal .modal-body').html(
                    '<div class="text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');
            },
            success: function(res) {
                if (res.return_type === 'json' && res.status) {
                    $('#showModal .modal-body').html(res.data);
                }

                if (res.return_type === 'html') {
                    window.location.href = res.data;
                }
            }
        })
    }

    function showByUrl(url) {
        window.location.href = url
    }

    function edit(id, redirect = '') {
        // editUrl defiend from every pages
        if (redirect != '') {
            editUrl = `{{ url('') }}/${redirect}/${id}/edit`;
        } else {
            editUrl = editUrl.replace(':id', id);
        }

        window.location.href = editUrl;
    }

    function destroy(id) {
        swal({
                title: "{{ trans('common.are_you_sure') }}",
                text: "{{ trans('common.delete_confirm_text') }}",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: destroyUrl.replace(':id', id),
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
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
            });
    }

    function checkAll(el) {
        if (el.checked) {
            $('.data-check').prop('checked', true);
            $('.data-check').trigger('change');
        } else {
            $('.data-check').prop('checked', false);
            $('.data-check').trigger('change');
        }
    }

    function actions(element, redirect = '', additionalElements = '') {
        // Destroy any existing popover to ensure a fresh start
        $(element).popover('dispose');
        let id = $(element).attr('popover-id');
        // Initialize the popover
        $(element).popover({
            html: true,
            content: function() {
                let content = `<div id="popover-content-` + $(element).attr('popover-id') + `" class="popover-custom-content">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="javascript:void(0);" title="{{ trans('common.edit') }}" onclick="edit('${id}', '${redirect}')" class="nav-link pb-0 text-info edit">
                                   <i class="fa fa-edit"></i>&nbsp;{{ trans('common.edit') }}
                                </a>
                            </li>
                            <div class="dropdown-divider"></div>
                            <li class="nav-item">
                                <a href="javascript:void(0);" title="{{ trans('common.delete') }}" onclick="destroy('${id}')" class="nav-link ${additionalElements == '' ? 'pt-0' : 'py-0'} text-danger">
                                    <i class="fa fa-trash"></i>&nbsp;{{ trans('common.delete') }}
                                </a>
                            </li>`;

                if (additionalElements != '') {
                    content += `<div class="dropdown-divider"></div>`;

                    JSON.parse(additionalElements).forEach(item => {
                        content += `<li class="nav-item">
                                <a href="${item.url}" title="${item.title}" class="nav-link pt-0 text-info">
                                   <i class="fa fa-link"></i>&nbsp;${item.title}
                                </a>
                            </li>`;
                    });
                }

                content += `
                        </ul>
                    </div>`;

                return content;
            },
            placement: 'bottom',
        });

        // Show the popover
        $(element).popover('show');
    }

    function getDatatable(el, url) {
        window.history.pushState("", "", url);

        $('.nav-link').attr('disabled', true);
        $('.nav-link').removeClass('active');
        $(el).addClass('active');

        table.ajax.url(url).load();
        $('.nav-link').attr('disabled', false);
    }

    function renderImage(data) {
        if (data != null) {
            if (data != '/images/default.png') {
                data = `{{ asset('') }}${data}`;
            }
            return `<a href="javascript:void(0)" onclick="showModalImage('${data}')">
                <img src="${data}" alt="${data}" class="img-fluid" style="width: 20px; height: 20px; object-fit: cover">
            </a>`
        }

        return '';
    }

    function renderContent(url, name, icon = {
        show: false,
        icon: ''
    }, showModal = false) {
        let content = ``;

        if (icon.show) {
            content += `<span class="badge badge-pill btn-outline-dark btn-transition" onclick="showModalDetail('${url}')" style="cursor: pointer">
                <i class="fa fa-tag"></i>
            </span>`
        }

        return `${content}<a href="${url}">${name}</a>`
    }

    function toggleFilter(show = true) {
        const sidebar = $('#filterSidebar');
        const overlay = $('#filterOverlay');
        // hide any visible tooltips when toggling filter
        $('[data-toggle="tooltip"]').tooltip('hide');
        if (show) {
            sidebar.addClass('show');
            overlay.addClass('show');
        } else {
            sidebar.removeClass('show');
            overlay.removeClass('show');
        }
    }

    // apply/reset are page-specific; define in page when needed
</script>

@include('js.bulk-action')
