@extends('layouts.html')

@section('head')
@endsection

@section('body')
    <div class="min-h-screen grid grid-rows-[auto_1fr]">
        <x-navbar/>
        @yield('body.main')
    </div>
@endsection
