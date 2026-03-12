@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        {{-- <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => "Check In / Check Out",
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => "Check In / Check Out"],
                    ],
                ])
            </div>
        </div> --}}

        <div class="row booking-layout">
            <div class="col-lg-3 booking-layout__sidebar">
                <div class="card mb-4 booking-filter-card">
                    <div class="card-body">
                        <form action="{{ route('booking.index') }}" method="GET">
                            <div class="form-group">
                                <label>{{ trans('common.booking.filter_guest_name') }}</label>
                                <input type="text" name="guest_name" class="form-control"
                                    value="{{ $filters['guest_name'] ?? '' }}"
                                    placeholder="{{ trans('common.booking.filter_guest_name_placeholder') }}"
                                    autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>{{ trans('common.booking.filter_player_name') }}</label>
                                <input type="text" name="player_name" class="form-control"
                                    value="{{ $filters['player_name'] ?? '' }}"
                                    placeholder="{{ trans('common.booking.filter_player_name_placeholder') }}"
                                    autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>{{ trans('common.booking.filter_room_name') }}</label>
                                <input type="text" name="room_name" class="form-control"
                                    value="{{ $filters['room_name'] ?? '' }}"
                                    placeholder="{{ trans('common.booking.filter_room_name_placeholder') }}"
                                    autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>{{ trans('common.status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ trans('common.all') }}</option>
                                    <option value="available" {{ ($filters['status'] ?? '') === 'available' ? 'selected' : '' }}>
                                        {{ trans('common.booking.available') }}
                                    </option>
                                    <option value="occupied" {{ ($filters['status'] ?? '') === 'occupied' ? 'selected' : '' }}>
                                        {{ trans('common.booking.occupied') }}
                                    </option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                               <i class="fa fa-filter"></i> {{ trans('common.search_text') }}
                            </button>
                            <a href="{{ route('booking.index') }}" class="btn btn-light btn-block mt-2">
                               <i class="fa fa-undo"></i> {{ trans('common.reset') }}
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 booking-layout__content">
                <div class="row booking-card-grid">
                    @forelse ($players as $player)
                        @php
                            $booking = $player->currentBooking;
                            $isOccupied = (bool) $booking;
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="card booking-card {{ $isOccupied ? 'booking-card--occupied' : 'booking-card--available' }}"
                                role="button"
                                tabindex="0"
                                data-booking-action="{{ $isOccupied ? 'checkout' : 'checkin' }}"
                                data-player-alias="{{ $player->alias }}"
                                data-checkin-url="{{ route('booking.store', $player->uuid) }}"
                                data-checkout-url="{{ route('booking.checkout', $player->uuid) }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="booking-card__alias">{{ $player->name }}</div>
                                            <div class="text-muted small">{{ $player->serial }}</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge {{ $isOccupied ? 'badge-warning' : 'badge-success' }} mb-1">
                                                {{ $isOccupied ? trans('common.booking.occupied') : trans('common.booking.available') }}
                                            </span>
                                            @if ($booking)
                                                <div class="booking-card__meta">
                                                    {{ trans('common.booking.checked_in_at') }}:
                                                    {{ optional($booking->checked_in_at)->format('d/m/Y H:i') ?? '-' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="booking-card__guest mb-3">
                                        <div class="booking-card__label">{{ trans('common.booking.guest_name') }}</div>
                                        <div class="booking-card__value">{{ $booking?->guest_name ?? '-' }}</div>
                                    </div>

                                    <div class="booking-card__guest">
                                        <div class="booking-card__label">{{ trans('common.booking.alias') }}</div>
                                        <div class="booking-card__value">{{ $player->alias }}</div>
                                    </div>

                                    {{-- @if ($booking)
                                        <button type="button" class="btn btn-outline-danger btn-block btn-checkout-booking"
                                            data-url="{{ route('booking.checkout', $player->uuid) }}">
                                           <i class="fa fa-times"></i> {{ trans('common.booking.checkout') }}
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success btn-block btn-open-booking-modal"
                                            data-player-alias="{{ $player->alias }}"
                                            data-url="{{ route('booking.store', $player->uuid) }}">
                                            <i class="fa fa-check"></i> {{ trans('common.booking.book_now') }}
                                        </button>
                                    @endif --}}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="booking-empty py-5 d-flex flex-column align-items-center">
                                <img src="{{ getMediaImageUrl('default/error.png', 200, 200) }}" alt="" style="opacity: 0.3">
                                <br>
                                <br>
                                <h5>
                                    {{ trans('common.no_data') }}
                                </h5>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="booking-modal" id="bookingModal" aria-hidden="true">
        <div class="booking-modal__backdrop" data-booking-modal-close></div>
        <div class="booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bookingModalLabel">
            <form id="bookingForm" class="booking-modal__content">
                @csrf
                <div class="booking-modal__header">
                    <div>
                        <h5 class="booking-modal__title" id="bookingModalLabel">{{ trans('common.booking.book_now') }}</h5>
                    </div>
                    <button type="button" class="booking-modal__close" data-booking-modal-close aria-label="{{ trans('common.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="booking-modal__body">
                    <div class="booking-modal__player" id="bookingPlayerLabel"></div>
                    <div class="form-group mb-0">
                        <label for="guest_name">{{ trans('common.booking.guest_name') }}</label>
                        <input type="text" class="form-control booking-modal__input" id="guest_name" name="guest_name"
                            maxlength="150" required autocomplete="off">
                    </div>
                </div>
                <div class="booking-modal__footer">
                    <button type="button" class="btn btn-light border" data-booking-modal-close>{{ trans('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ trans('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="booking-modal" id="checkoutBillModal" aria-hidden="true">
        <div class="booking-modal__backdrop" data-booking-modal-close></div>
        <div class="booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="checkoutBillModalLabel">
            <div class="booking-modal__content">
                <div class="booking-modal__header">
                    <div>
                        <h5 class="booking-modal__title" id="checkoutBillModalLabel">{{ trans('common.booking.checkout') }}</h5>
                    </div>
                    <button type="button" class="booking-modal__close" data-booking-modal-close aria-label="{{ trans('common.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="booking-modal__body">
                    <div class="booking-modal__player" id="checkoutBillPlayerLabel"></div>
                    <div class="booking-modal__player-subtext" id="checkoutBillGuestLabel"></div>
                    <div class="alert alert-warning d-none" id="checkoutBillError"></div>
                    <p class="text-muted mb-3" id="checkoutBillDescription">{{ trans('common.booking.pending_bill_description') }}</p>
                    <div class="booking-bill-total d-none" id="checkoutBillTotalSection">
                        <span>{{ trans('common.booking.pending_bill_total') }}</span>
                        <button type="button" class="booking-bill-total__link" id="checkoutBillTotalLink">0</button>
                    </div>
                    <div class="custom-control custom-checkbox mt-3 d-none" id="checkoutBillIgnoreWrap">
                        <input type="checkbox" class="custom-control-input" id="checkoutBillConfirmPaid">
                        <label class="custom-control-label" for="checkoutBillConfirmPaid">
                            {{ trans('common.booking.pending_bill_ignore') }}
                        </label>
                    </div>
                </div>
                <div class="booking-modal__footer">
                    <button type="button" class="btn btn-light border" data-booking-modal-close>{{ trans('common.cancel') }}</button>
                    <button type="button" class="btn btn-success" id="checkoutBillSubmit" disabled>
                        {{ trans('common.booking.pending_bill_action') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @parent
    <style>
        .booking-layout {
            min-height: calc(100vh - 220px);
        }

        .booking-layout__sidebar,
        .booking-layout__content {
            min-height: calc(100vh - 220px);
        }

        .booking-filter-card {
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 150px);
            overflow-y: auto;
        }

        .booking-card-grid {
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            padding-right: 0.35rem;
            align-content: flex-start;
        }

        .booking-card {
            border: 1px solid #dfe6ee;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
            margin-bottom: 1.5rem;
            overflow: hidden;
            cursor: pointer;
            transform: translateY(0);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .booking-card--available {
            background: linear-gradient(180deg, #ffffff 0%, #f4fbf6 100%);
        }

        .booking-card--occupied {
            background: linear-gradient(180deg, #ffffff 0%, #fff7ed 100%);
        }

        .booking-card--available:hover {
            background: linear-gradient(180deg, #f8fffb 0%, #dcfce7 100%);
            border-color: #86efac;
        }

        .booking-card--occupied:hover {
            background: linear-gradient(180deg, #fffaf5 0%, #ffedd5 100%);
            border-color: #fdba74;
        }

        .booking-card__alias {
            font-size: 1.25rem;
            font-weight: 700;
            color: #22304a;
            letter-spacing: 0.02em;
        }

        .booking-card__label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7b8798;
            margin-bottom: 0.35rem;
        }

        .booking-card__value {
            font-size: 1.05rem;
            font-weight: 600;
            color: #18202c;
        }

        .booking-card__meta {
            font-size: 0.76rem;
            font-weight: 600;
            color: #64748b;
        }

        .booking-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        .booking-card:active {
            transform: translateY(-1px) scale(0.995);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.1);
        }

        .booking-card:focus {
            outline: none;
        }

        .booking-card:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22), 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        .booking-empty {
            padding: 2rem 1.25rem;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            background: #fff;
            text-align: center;
            color: #64748b;
            font-weight: 600;
        }

        .booking-modal {
            position: fixed;
            inset: 0;
            z-index: 1055;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .booking-modal.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .booking-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(4px);
        }

        .booking-modal__dialog {
            position: relative;
            width: 100%;
            max-width: 460px;
            z-index: 1;
        }

        .booking-modal__content {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #d8e1ea;
            border-radius: 22px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .booking-modal__header,
        .booking-modal__body,
        .booking-modal__footer {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .booking-modal__header {
            padding-top: 1.4rem;
            padding-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .booking-modal__eyebrow {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #7b8798;
            margin-bottom: 0.35rem;
        }

        .booking-modal__title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #18202c;
        }

        .booking-modal__close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 999px;
            background: #eef2f7;
            color: #516173;
            font-size: 1.4rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .booking-modal__body {
            padding-top: 0.25rem;
            padding-bottom: 1.25rem;
        }

        .booking-modal__player {
            margin-bottom: 1rem;
            padding: 0.95rem 1rem;
            border-radius: 14px;
            background: #f1f5f9;
            border: 1px solid #dbe4ee;
            font-size: 1rem;
            font-weight: 600;
            color: #22304a;
        }

        .booking-modal__player-subtext {
            margin-top: -0.45rem;
            margin-bottom: 1rem;
            font-size: 0.82rem;
            color: #64748b;
        }

        .booking-modal__input {
            height: 48px;
            border-radius: 12px;
        }

        .booking-modal__footer {
            padding-top: 1rem;
            padding-bottom: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid #e7edf3;
        }

        .booking-bill-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e7edf3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1rem;
            color: #22304a;
        }

        .booking-bill-total__link {
            border: 0;
            background: transparent;
            padding: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #2563eb;
            text-decoration: underline;
            cursor: pointer;
        }

        body.booking-modal-open {
            overflow: hidden;
        }

        @media (max-width: 991.98px) {
            .booking-layout,
            .booking-layout__sidebar,
            .booking-layout__content {
                min-height: auto;
            }

            .booking-filter-card,
            .booking-card-grid {
                position: static;
                max-height: none;
                overflow: visible;
            }
        }
    </style>
@endsection

@section('js')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingModal = document.getElementById('bookingModal');
            const checkoutBillModal = document.getElementById('checkoutBillModal');
            const bookingForm = document.getElementById('bookingForm');
            const guestNameInput = document.getElementById('guest_name');
            const bookingPlayerLabel = document.getElementById('bookingPlayerLabel');
            const checkoutBillPlayerLabel = document.getElementById('checkoutBillPlayerLabel');
            const checkoutBillGuestLabel = document.getElementById('checkoutBillGuestLabel');
            const checkoutBillError = document.getElementById('checkoutBillError');
            const checkoutBillDescription = document.getElementById('checkoutBillDescription');
            const checkoutBillTotalSection = document.getElementById('checkoutBillTotalSection');
            const checkoutBillTotalLink = document.getElementById('checkoutBillTotalLink');
            const checkoutBillIgnoreWrap = document.getElementById('checkoutBillIgnoreWrap');
            const checkoutBillConfirmPaid = document.getElementById('checkoutBillConfirmPaid');
            const checkoutBillSubmit = document.getElementById('checkoutBillSubmit');
            const modalCloseButtons = document.querySelectorAll('[data-booking-modal-close]');
            let currentBookingUrl = '';
            let currentCheckoutUrl = '';
            let currentBillDetailUrl = '';
            let currentCheckoutHasPendingBill = false;

            function openModal(modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('booking-modal-open');
            }

            function closeModal(modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            function closeAllModals() {
                closeModal(bookingModal);
                closeModal(checkoutBillModal);
                document.body.classList.remove('booking-modal-open');
            }

            function openBookingModal() {
                openModal(bookingModal);
                setTimeout(() => guestNameInput.focus(), 100);
            }

            function formatCurrency(amount) {
                return Number(amount || 0).toLocaleString('id-ID');
            }

            function setCheckoutBillError(message) {
                if (!message) {
                    checkoutBillError.textContent = '';
                    checkoutBillError.classList.add('d-none');
                    return;
                }

                checkoutBillError.textContent = message;
                checkoutBillError.classList.remove('d-none');
            }

            function renderPendingBillModal(summary) {
                const playerAlias = summary.player_alias || '';
                const guestName = summary.guest_name || '-';

                checkoutBillPlayerLabel.textContent = playerAlias;
                checkoutBillGuestLabel.textContent = guestName !== '-'
                    ? `Guest: ${guestName}`
                    : '';
                checkoutBillGuestLabel.classList.toggle('d-none', guestName === '-');
                currentCheckoutHasPendingBill = Boolean(summary.has_pending_bill);
                checkoutBillDescription.textContent = currentCheckoutHasPendingBill
                    ? "{{ trans('common.booking.pending_bill_description') }}"
                    : "{{ trans('common.booking.checkout_confirm') }}";
                checkoutBillTotalSection.classList.toggle('d-none', !currentCheckoutHasPendingBill);
                checkoutBillIgnoreWrap.classList.toggle('d-none', !currentCheckoutHasPendingBill);
                checkoutBillTotalLink.textContent = formatCurrency(summary.total_amount);
                currentBillDetailUrl = summary.detail_url || '';
                setCheckoutBillError(summary.message || '');
                checkoutBillConfirmPaid.checked = false;
                checkoutBillSubmit.disabled = currentCheckoutHasPendingBill;
                openModal(checkoutBillModal);
            }

            function previewCheckout(url) {
                $.ajax({
                    url: url,
                    method: 'POST',
                    global: false,
                    data: {
                        _token: "{{ csrf_token() }}",
                        preview_checkout: 1
                    },
                    success: function(res) {
                        renderPendingBillModal(res.data || {});
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        toastr["error"](response.message || "{{ trans('common.error.500') }}", "Error");
                    }
                });
            }

            function submitCheckout(url, ignorePendingBill) {
                $.ajax({
                    url: url,
                    method: 'POST',
                    global: false,
                    data: {
                        _token: "{{ csrf_token() }}",
                        ignore_pending_bill: ignorePendingBill ? 1 : 0
                    },
                    success: function(res) {
                        closeAllModals();
                        toastr["success"](res.message, "Success");
                        window.location.reload();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};

                        if (xhr.status === 422 && response.requires_bill_settlement && response.data) {
                            response.data.message = response.message || '';
                            renderPendingBillModal(response.data);
                            return;
                        }

                        if (checkoutBillModal.classList.contains('is-open')) {
                            setCheckoutBillError(response.message || "{{ trans('common.error.500') }}");
                            return;
                        }

                        toastr["error"](response.message || "{{ trans('common.error.500') }}", "Error");
                    }
                });
            }

            function handleBookingCardAction(card) {
                const action = card.dataset.bookingAction;

                if (action === 'checkin') {
                    currentBookingUrl = card.dataset.checkinUrl;
                    bookingPlayerLabel.textContent = card.dataset.playerAlias;
                    guestNameInput.value = '';
                    openBookingModal();
                    return;
                }

                if (action === 'checkout') {
                    currentCheckoutUrl = card.dataset.checkoutUrl;
                    previewCheckout(currentCheckoutUrl);
                }
            }

            document.querySelectorAll('.booking-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    handleBookingCardAction(card);
                });

                card.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        handleBookingCardAction(card);
                    }
                });
            });

            modalCloseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    closeAllModals();
                });
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && (bookingModal.classList.contains('is-open') || checkoutBillModal.classList.contains('is-open'))) {
                    closeAllModals();
                }
            });

            bookingForm?.addEventListener('submit', function(event) {
                event.preventDefault();

                $.ajax({
                    url: currentBookingUrl,
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        guest_name: guestNameInput.value
                    },
                    success: function(res) {
                        closeAllModals();
                        toastr["success"](res.message, "Success");
                        window.location.reload();
                    },
                    error: function(xhr) {
                        toastr["error"](xhr.responseJSON?.message || "{{ trans('common.error.500') }}", "Error");
                    }
                });
            });

            checkoutBillConfirmPaid?.addEventListener('change', function() {
                checkoutBillSubmit.disabled = currentCheckoutHasPendingBill ? !this.checked : false;
            });

            checkoutBillSubmit?.addEventListener('click', function() {
                if (!currentCheckoutUrl) {
                    return;
                }

                if (currentCheckoutHasPendingBill && !checkoutBillConfirmPaid.checked) {
                    setCheckoutBillError("{{ trans('common.booking.pending_bill_settlement_required') }}");
                    return;
                }

                submitCheckout(currentCheckoutUrl, currentCheckoutHasPendingBill);
            });

            checkoutBillTotalLink?.addEventListener('click', function() {
                if (!currentBillDetailUrl) {
                    return;
                }

                window.open(currentBillDetailUrl, '_blank');
            });
        });
    </script>
@endsection
