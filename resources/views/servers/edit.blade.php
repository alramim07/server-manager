@extends('layouts.app')

@section('title', 'Edit server')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-6 text-xl font-bold">Edit server</h1>

        <form method="POST" action="{{ route('servers.update', $server) }}">
            @csrf
            @method('PUT')

            @include('servers._form', ['server' => $server, 'cancelUrl' => route('dashboard')])
        </form>
    </div>
@endsection
