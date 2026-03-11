@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div>
                        Pantry Transactions
                        <div class="page-title-subheading">
                            Monitor pantry orders and view transaction details.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-header">Transaction List</div>
                    <div class="card-body p-2">
                        <div class="transaction-status-tabs mb-3">
                            <button type="button"
                                class="badge badge-pill transaction-filter {{ $activeStatus === 'all' ? 'badge-primary' : 'badge-light' }}"
                                data-status="all">
                                All (<span id="count-all">{{ $statusCounts['all'] ?? 0 }}</span>)
                            </button>
                            <button type="button"
                                class="badge badge-pill transaction-filter {{ $activeStatus === 'ordered' ? 'badge-info' : 'badge-light' }}"
                                data-status="ordered">
                                Ordered (<span id="count-ordered">{{ $statusCounts['ordered'] ?? 0 }}</span>)
                            </button>
                            <button type="button"
                                class="badge badge-pill transaction-filter {{ $activeStatus === 'processing' ? 'badge-warning' : 'badge-light' }}"
                                data-status="processing">
                                Processing (<span id="count-processing">{{ $statusCounts['processing'] ?? 0 }}</span>)
                            </button>
                            <button type="button"
                                class="badge badge-pill transaction-filter {{ $activeStatus === 'completed' ? 'badge-success' : 'badge-light' }}"
                                data-status="completed">
                                Completed (<span id="count-completed">{{ $statusCounts['completed'] ?? 0 }}</span>)
                            </button>
                            <button type="button"
                                class="badge badge-pill transaction-filter {{ $activeStatus === 'cancelled' ? 'badge-danger' : 'badge-light' }}"
                                data-status="cancelled">
                                Cancelled (<span id="count-cancelled">{{ $statusCounts['cancelled'] ?? 0 }}</span>)
                            </button>
                        </div>
                        <div id="transaction-list-container">
                            @include('pages.transactions.components.list', [
                                'transactions' => $transactions,
                                'selectedTransaction' => $selectedTransaction,
                            ])
                        </div>
                        <div id="transaction-list-loading" class="text-center text-muted small py-2 d-none">
                            Loading more transactions...
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div id="transaction-detail-container">
                    @include('pages.transactions.components.detail', [
                        'selectedTransaction' => $selectedTransaction,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @parent
    <style>
        #transaction-list-container {
            max-height: 72vh;
            overflow-y: auto;
            padding-right: 4px;
        }

        .transaction-status-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .transaction-status-tabs .badge {
            cursor: pointer;
            font-size: 12px;
            padding: 7px 12px;
            border: 0;
        }

        .transaction-list-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 14px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: inherit;
            text-decoration: none !important;
            margin-bottom: 10px;
            background: #fff;
            cursor: pointer;
        }

        .transaction-list-item:hover,
        .transaction-list-item--active {
            border-color: #3f6ad8;
            box-shadow: 0 0 0 1px #3f6ad8 inset;
        }

        .transaction-meta__label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .transaction-meta__value {
            font-weight: 600;
            color: #212529;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .transaction-item__icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            flex: 0 0 38px;
            overflow: hidden;
        }

        .transaction-item__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .transaction-item__content {
            flex: 1;
            min-width: 0;
        }

        .transaction-item__qty,
        .transaction-item__price,
        .transaction-item__total {
            width: 90px;
            flex: 0 0 90px;
        }

        .transaction-summary {
            max-width: 280px;
            margin-left: auto;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 767.98px) {
            .transaction-item {
                flex-wrap: wrap;
            }

            .transaction-item__qty,
            .transaction-item__price,
            .transaction-item__total {
                width: auto;
                flex: 1 1 30%;
            }
        }
    </style>
@endsection

@section('js')
    @parent
    <script src="{{ env('WEBSOCKET') }}/socket.io/socket.io.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const listContainer = document.getElementById('transaction-list-container');
            const detailContainer = document.getElementById('transaction-detail-container');
            const loadingIndicator = document.getElementById('transaction-list-loading');
            const filterButtons = document.querySelectorAll('.transaction-filter');
            let nextPage = {{ $nextTransactionPage }};
            let hasMore = {{ $hasMoreTransactions ? 'true' : 'false' }};
            let isLoading = false;
            let activeStatus = '{{ $activeStatus }}';

            function updateFilterButtons(status) {
                filterButtons.forEach(function(button) {
                    button.classList.remove('badge-primary', 'badge-info', 'badge-warning', 'badge-success',
                        'badge-danger');
                    button.classList.add('badge-light');

                    if (button.dataset.status !== status) {
                        return;
                    }

                    button.classList.remove('badge-light');

                    if (status === 'ordered') {
                        button.classList.add('badge-info');
                    } else if (status === 'processing') {
                        button.classList.add('badge-warning');
                    } else if (status === 'completed') {
                        button.classList.add('badge-success');
                    } else if (status === 'cancelled') {
                        button.classList.add('badge-danger');
                    } else {
                        button.classList.add('badge-primary');
                    }
                });
            }

            function bindTransactionTriggers(scope) {
                scope.querySelectorAll('.transaction-trigger').forEach(function(trigger) {
                    if (trigger.dataset.bound === 'true') {
                        return;
                    }

                    trigger.dataset.bound = 'true';
                    trigger.addEventListener('click', function() {
                        const transactionId = this.dataset.transactionId;

                        listContainer.querySelectorAll('.transaction-trigger').forEach(function(
                            item) {
                            item.classList.remove('transaction-list-item--active');
                        });

                        this.classList.add('transaction-list-item--active');
                        loadTransactionDetail(transactionId);
                    });
                });
            }

            function bindStatusButtons() {
                detailContainer.querySelectorAll('.transaction-status-btn').forEach(function(button) {
                    if (button.dataset.bound === 'true') {
                        return;
                    }

                    button.dataset.bound = 'true';
                    button.addEventListener('click', function() {
                        const transactionId = this.dataset.id;
                        const status = this.dataset.status;
                        const formData = new FormData();
                        formData.append('status', status);
                        formData.append('_token', '{{ csrf_token() }}');

                        fetch(`{{ url('transactions/status') }}/${transactionId}`, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            })
                            .then(function(response) {
                                return response.json().then(function(data) {
                                    return {
                                        ok: response.ok,
                                        data: data
                                    };
                                });
                            })
                            .then(function(result) {
                                if (!result.ok) {
                                    throw new Error(result.data.message ||
                                        'Failed to update transaction.');
                                }

                                detailContainer.innerHTML = result.data.detail_html;
                                bindStatusButtons();
                                reloadTransactionList(transactionId);
                                updateCount(result.data.detail_count);
                                toastr["success"](result.data.message, "Success");
                            })
                            .catch(function(error) {
                                toastr["error"](error.message, "Error");
                            });
                    });
                });
            }

            function loadTransactionDetail(transactionId) {
                fetch(`{{ route('transactions.index') }}?transaction_id=${transactionId}&status=${activeStatus}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) {
                        return response.text();
                    })
                    .then(function(html) {
                        detailContainer.innerHTML = html;
                        bindStatusButtons();
                    })
                    .catch(function() {
                        toastr["error"]('Failed to load transaction detail.', "Error");
                    });
            }

            function updateCount(detailCount) {
                $('#count-all').text(detailCount.all);
                $('#count-processing').text(detailCount.processing);
                $('#count-ordered').text(detailCount.ordered);
                $('#count-completed').text(detailCount.completed);
                $('#count-cancelled').text(detailCount.cancelled);
            }

            function loadMoreTransactions() {
                if (!hasMore || isLoading) {
                    return;
                }

                isLoading = true;
                loadingIndicator.classList.remove('d-none');

                fetch(`{{ route('transactions.index') }}?partial=list&page=${nextPage}&status=${activeStatus}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = data.html;

                        while (wrapper.firstChild) {
                            listContainer.appendChild(wrapper.firstChild);
                        }

                        bindTransactionTriggers(listContainer);
                        hasMore = data.has_more;
                        nextPage = data.next_page;
                    })
                    .catch(function() {
                        toastr["error"]('Failed to load more transactions.', "Error");
                    })
                    .finally(function() {
                        isLoading = false;
                        loadingIndicator.classList.add('d-none');
                    });
            }

            function reloadTransactionList(activeId) {
                const transactionQuery = activeId ? `&transaction_id=${activeId}` : '';

                fetch(`{{ route('transactions.index') }}?partial=list&page=1&status=${activeStatus}${transactionQuery}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        listContainer.innerHTML = data.html;
                        bindTransactionTriggers(listContainer);
                        hasMore = data.has_more;
                        nextPage = data.next_page;
                        listContainer.scrollTop = 0;
                    })
                    .catch(function() {
                        toastr["error"]('Failed to refresh transaction list.', "Error");
                    });
            }

            filterButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    activeStatus = this.dataset.status;
                    updateFilterButtons(activeStatus);
                    reloadTransactionList(null);

                    fetch(`{{ route('transactions.index') }}?status=${activeStatus}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            return response.text();
                        })
                        .then(function(html) {
                            detailContainer.innerHTML = html;
                            bindStatusButtons();
                        });
                });
            });

            listContainer.addEventListener('scroll', function() {
                const nearBottom = listContainer.scrollTop + listContainer.clientHeight >= listContainer
                    .scrollHeight - 80;
                if (nearBottom) {
                    loadMoreTransactions();
                }
            });


            const socket = io(`{{ env('WEBSOCKET') }}`);
            socket.on("new-order", function(data) {
                reloadTransactionList(null);

                toastr["success"](`Order ${data.player_alias}`, "Success");

                playNotificationSound();
            });

            function playNotificationSound() {
                const audio = new Audio(`{{ asset('template/sound/bell.mp3') }}`);
                audio.play();
            }

            bindTransactionTriggers(document);
            bindStatusButtons();
            updateFilterButtons(activeStatus);
        });
    </script>
@endsection
