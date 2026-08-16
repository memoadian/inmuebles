@extends('layouts.app')

@section('title', 'Nueva propiedad')

@section('content')
    <form method="POST" action="{{ route('properties.store') }}">
        @csrf
        @include('properties._form')
    </form>
@endsection
