@extends('frontend.layouts.app')
@section('content')
<section class="py-4 py-lg-5" style="background-color: var(--bg-body);">
	<div class="container">
		<div class="d-flex align-items-start flex-column flex-lg-row">
			@include('frontend.member.sidebar')
			
			<div class="aiz-user-panel w-100 overflow-hidden">
				@yield('panel_content')
			</div>
		</div>
	</div>
</section>
@endsection