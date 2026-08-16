@extends('layouts.app')

@section('title', 'Nuevo permiso')

@section('content')
    <form method="POST" action="{{ route('permissions.store') }}" class="max-w-lg">
        @csrf
        @include('permissions._form', ['permission' => null])
    </form>
@endsection
