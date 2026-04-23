@extends('admin.layouts.app')
@section('title', 'Modifier le statut')
@section('page-title', 'Modifier : ' . $agencyStatus->name)

@section('content')
<div class="max-w-lg">
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <form action="{{ route('admin.agency-statuses.update', $agencyStatus) }}" method="POST">
            @csrf @method('PATCH')
            @include('admin.agency-statuses._form', ['status' => $agencyStatus])
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white font-medium px-6 py-2 rounded-lg text-sm">
                    Enregistrer
                </button>
                <a href="{{ route('admin.agency-statuses.index') }}" class="text-gray-500 text-sm px-4 py-2">Annuler</a>
            </div>
        </form>
    </div>

    {{-- Agences utilisant ce statut --}}
    @php $count = $agencyStatus->agencies()->count(); @endphp
    @if($count > 0)
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
        ℹ <strong>{{ $count }} agence(s)</strong> utilisent ce statut. La modification de la remise s'appliquera aux prochains calculs de prix.
    </div>
    @endif
</div>
@endsection
