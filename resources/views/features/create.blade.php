@extends('layouts.app')

@section('title', 'Nueva amenidad')

@section('content')
    <form method="POST" action="{{ route('features.store') }}" class="max-w-lg">
        @csrf
        @include('features._form', ['feature' => null])
    </form>
@endsection
