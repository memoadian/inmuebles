@extends('layouts.app')

@section('title', 'Editar amenidad')

@section('content')
    <form method="POST" action="{{ route('features.update', $feature) }}" class="max-w-lg">
        @csrf
        @method('PUT')
        @include('features._form')
    </form>
@endsection
