@extends('layouts.app')

@section('title', 'Editar permiso')

@section('content')
    <form method="POST" action="{{ route('permissions.update', $permission) }}" class="max-w-lg">
        @csrf
        @method('PUT')
        @include('permissions._form')
    </form>
@endsection
