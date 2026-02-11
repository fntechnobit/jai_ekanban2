<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <div aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ url('dashboard') }}"><i class="fa-solid fa-home"></i></a></li>
                @foreach($breadcrumbs as $index => $crumb)
                    @if(isset($crumb['is_current']) && $crumb['is_current'])
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb['name'] }}</li>
                    @elseif($crumb['url'])
                        <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
                    @else
                        <li class="breadcrumb-item">{{ $crumb['name'] }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
        <h1 class="page-title fw-medium fs-18 mb-0">{{ $title }}</h1>
    </div>
</div>
