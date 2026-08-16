@extends('layouts.app')

@section('title', 'Nuevo usuario')

@section('content')
    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl">
        @csrf
        @include('users._form', ['user' => null])
    </form>
@endsection
