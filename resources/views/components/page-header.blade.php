<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    @foreach($breadcrumbs as $index => $crumb)
                        @if(isset($crumb['is_current']) && $crumb['is_current'])
                            <li class="breadcrumb-item active">{{ $crumb['name'] }}</li>
                        @elseif($crumb['url'])
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
                        @else
                            <li class="breadcrumb-item">{{ $crumb['name'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>
