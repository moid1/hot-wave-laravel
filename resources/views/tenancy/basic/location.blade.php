@extends('tenancy.layouts.app')

@section('content')
    <div id="device-dashboard" data-user="{{ Auth::user()->username }}">Locations</div>
@endsection
@section('javascript')
    <script src={{asset('js/deviceDashboard.js')}}></script>
@stop
