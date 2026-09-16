@extends('layouts.app')
@section('title', 'Dashboard — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="/my-properties" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">My Properties</div>
        <div class="text-gray-500 text-sm">Manage your listings</div>
    </a>
    <a href="/escrow" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">Escrow</div>
        <div class="text-gray-500 text-sm">Active transactions</div>
    </a>
    <a href="/credit-score" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">Credit Score</div>
        <div class="text-gray-500 text-sm">View your score</div>
    </a>
</div>
@endsection