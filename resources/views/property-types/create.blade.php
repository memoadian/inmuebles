@extends('layouts.app')

@section('title', 'Nuevo tipo de inmueble')

@section('content')
    <form method="POST" action="{{ route('property-types.store') }}" class="max-w-lg">
        @csrf
        @include('property-types._form', ['type' => null])
    </form>
@endsection
