@extends('admin.layouts.app')
@section('title', 'Nouveau statut agence')
@section('page-title', 'Créer un statut tarifaire')

@section('content')
<div class="max-w-lg">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.agency-statuses.store') }}" method="POST">
            @csrf
            @include('admin.agency-statuses._form')
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">
                    Créer le statut
                </button>
                <a href="{{ route('admin.agency-statuses.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
