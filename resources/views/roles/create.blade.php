@extends('layouts.app')

@section('title', 'Nuevo rol')

@section('content')
    <form method="POST" action="{{ route('roles.store') }}" class="max-w-3xl">
        @csrf
        @include('roles._form', ['role' => null])
    </form>
@endsection
