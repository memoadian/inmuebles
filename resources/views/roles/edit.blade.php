@extends('layouts.app')

@section('title', 'Editar rol')

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('roles._form')
    </form>
@endsection
