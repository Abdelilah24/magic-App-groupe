@extends('admin.layouts.app')
@section('title', 'Nouveau supplément')
@section('page-title', 'Nouveau supplément')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.supplements.store') }}" method="POST">
            @csrf
            @include('admin.supplements._form', ['supplement' => null])
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">
                    Créer le supplément
                </button>
                <a href="{{ route('admin.supplements.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
