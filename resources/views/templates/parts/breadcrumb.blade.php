<div class="page-title-heading">
    <div class="page-title-icon">
        <i class="{{ $icon ?? 'fa fa-list' }} icon-gradient bg-mean-fruit"></i>
    </div>
    <div>
        <div class="text-title">{{ $title }}</div>
        <div class="page-title-subheading">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($breadcrumbs as $breadcrumb)
                        @if ($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['href'] }}">{{ $breadcrumb['label'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
</div>
