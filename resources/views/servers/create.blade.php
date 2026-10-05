@extends('layouts.app')

@section('title', 'New server')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-6 text-xl font-bold">New server</h1>

        <form method="POST" action="{{ route('servers.store') }}">
            @csrf

            @include('servers._form', ['server' => null, 'cancelUrl' => route('servers.index')])
        </form>
    </div>
@endsection
