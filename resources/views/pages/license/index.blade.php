@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.license.title_singular'),
                    'icon' => $icon,
                    'breadcrumbs' => [['href' => '#', 'label' => trans('common.license.title_singular')]],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card license-card mb-4">
                    <div class="card-body p-4">
                        <h4 class="license-card__title">App Details</h4>

                        <div class="license-detail-list">
                            @foreach ($appDetails as $detail)
                                <div class="license-detail-row">
                                    <div class="license-detail-row__label">{{ $detail['label'] }}</div>
                                    <div class="license-detail-row__value">
                                        <span>{{ $detail['value'] }}</span>
                                        @if (!empty($detail['meta']))
                                            <span class="license-status {{ $detail['meta_class'] ?? '' }}">
                                                {{ $detail['meta'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card license-card mb-4">
                    <div class="card-body p-4">
                        <h4 class="license-card__title">Core Features</h4>

                        <div class="license-feature-list">
                            @foreach ($coreFeatures as $item)
                                <div class="license-feature-row">
                                    <div class="license-feature-row__copy">
                                        <div class="license-feature-row__name">{{ $item['name'] }}</div>
                                        <div class="license-feature-row__description">{{ $item['description'] }}</div>
                                    </div>
                                    <div class="license-feature-row__status">{{ $item['status'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .license-card {
            border: 1px solid #e9edf5;
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }

        .license-card__title {
            margin-bottom: 20px;
            color: #25324b;
            font-size: 28px;
            font-weight: 600;
        }

        .license-detail-row,
        .license-feature-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 0;
            border-top: 1px solid #eef2f7;
        }

        .license-detail-row:first-child,
        .license-feature-row:first-child {
            border-top: 0;
            padding-top: 6px;
        }

        .license-detail-row__label,
        .license-feature-row__name {
            color: #25324b;
            font-weight: 600;
        }

        .license-detail-row__value {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            color: #25324b;
            text-align: right;
        }

        .license-status,
        .license-feature-row__status {
            color: #9aa5b1;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .license-feature-row__copy {
            max-width: 75%;
        }

        .license-feature-row__description {
            margin-top: 4px;
            color: #677489;
            font-size: 14px;
            line-height: 1.5;
        }

        @media (max-width: 767.98px) {
            .license-card__title {
                font-size: 22px;
            }

            .license-detail-row,
            .license-feature-row,
            .license-detail-row__value {
                align-items: flex-start;
                flex-direction: column;
            }

            .license-detail-row__value,
            .license-feature-row__status {
                text-align: left;
            }

            .license-feature-row__copy {
                max-width: 100%;
            }
        }
    </style>
@endsection
