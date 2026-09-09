{{-- Breadcrumb Component --}}
@props(['title' => 'Dashboard', 'links' => []])

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ $title }}</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    @foreach($links as $link)
                    <li class="breadcrumb-item">
                        @if(!empty($link['url']))
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @else
                        {{ $link['label'] }}
                        @endif
                    </li>
                    @endforeach
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
