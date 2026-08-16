@extends('layouts.app')

@section('title', 'Editar tipo de inmueble')

@section('content')
    <form method="POST" action="{{ route('property-types.update', $type) }}" class="max-w-lg">
        @csrf
        @method('PUT')
        @include('property-types._form')
    </form>
@endsection
