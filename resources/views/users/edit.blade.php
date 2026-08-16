@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}" class="max-w-2xl">
        @csrf
        @method('PUT')
        @include('users._form')
    </form>
@endsection
