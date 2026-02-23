@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.user.title_singular'),
                    'breadcrumbs' => [
                        [
                            'href' => route('users.index'),
                            'label' => trans('common.user.list_of_user'),
                        ],
                        [
                            'href' => '#',
                            'label' => $user->profile->name,
                        ],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('users.index'),
                    ])
                    @include('partials.buttons.btn-edit', [
                        'url' => route('users.edit', $user->id),
                        'label' => trans('common.user.title_singular'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-body">
                        @php
                            // README
                            // key: digunakan saat ingin mengambil data berdasarkan index array
                            // slug: untuk halaman masing-masing detailsnya
                            // label: untuk title
                            // href: untuk link ke halaman
                            // datatable_url: untuk get data
                            $listItems = [
                                'info' => [
                                    'slug' => 'info',
                                    'label' => trans('common.info'),
                                    'href' => url('users/' . $user->id),
                                ],
                                'asset' => [
                                    'slug' => 'asset',
                                    'label' => trans('common.asset.title_singular'),
                                    'href' => url('users/' . $user->id . '/detail/asset'),
                                    'datatable_url' => url('assets') . '?user_id=' . $user->id,
                                ],
                                'license' => [
                                    'slug' => 'license',
                                    'label' => trans('common.license.title_singular'),
                                    'href' => url('users/' . $user->id . '/detail/license'),
                                    'datatable_url' => url('licenses') . '?user_id=' . $user->id,
                                ],
                                'consumable' => [
                                    'slug' => 'consumable',
                                    'label' => trans('common.consumable.title_singular'),
                                    'href' => url('users/' . $user->id . '/detail/consumable'),
                                    'datatable_url' => url('consumables') . '?user_id=' . $user->id,
                                ],
                                'accessory' => [
                                    'slug' => 'accessory',
                                    'label' => trans('common.accessory.title_singular'),
                                    'href' => url('users/' . $user->id . '/detail/accessory'),
                                    'datatable_url' => url('accessories') . '?user_id=' . $user->id,
                                ],
                            ];

                            // untuk url dari datatablenya yang ada dimasing-masing blade component
                            $detailUrl = $listItems[$detail]['datatable_url'] ?? '';
                        @endphp
                        <ul class="nav nav-tabs">
                            @foreach ($listItems as $key => $list)
                                <li class="nav-item">
                                    <a data-toggle="tab" data-target="#{{ $list['slug'] }}" onclick="location.href='{{ $list['href'] }}'" class="{{ $detail == $list['slug'] ? 'active' : '' }} nav-link">{{ $list['label'] }}

                                        @if (isset($tabCount[$key]) && $tabCount[$key] > 0)
                                            <span class="badge badge-info rounded p-1">{{ $tabCount[$key] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="{{ $detail }}" role="tabpanel">
                                @includeIf('pages.users.details.' . $detail)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('js.datatable')
@endsection
