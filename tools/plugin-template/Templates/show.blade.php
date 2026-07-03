@extends($layout)

@section('content')
    <div class="pageheader">
        <div class="pageicon"><i class="fa fa-puzzle-piece"></i></div>
        <div class="pagetitle">
            <h1>{{ __('__PLUGIN_LOWER__.headline') }}</h1>
        </div>
    </div>

    <div class="maincontent">
        <div class="maincontentinner">
            <h3>{{ __('__PLUGIN_LOWER__.text') }}</h3>
        </div>
    </div>
@endsection
