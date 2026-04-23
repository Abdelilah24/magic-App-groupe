@extends('admin.layouts.app')
@section('title', 'Templates PDF')
@section('page-title', 'Templates PDF')
@section('page-subtitle', 'Gérer la mise en page des documents PDF générés automatiquement')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm"> {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"> @forelse($templates as $tpl)
    <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3 hover:shadow-md transition"> <div class="flex items-start justify-between gap-2"> <div> <h3 class="font-semibold text-gray-900 text-sm">{{ $tpl->name }}</h3> <p class="text-xs text-gray-400 mt-0.5">{{ $tpl->description }}</p> </div> <span class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full {{ $tpl->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}"> {{ $tpl->is_active ? 'Actif' : 'Inactif' }}
            </span> </div> @if($tpl->placeholders)
        <div class="flex flex-wrap gap-1"> @foreach(array_slice($tpl->placeholders, 0, 4) as $ph)
            <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded font-mono"> &#123;&#123; {{ $ph['key'] }} &#125;&#125;
            </span> @endforeach
            @if(count($tpl->placeholders) > 4)
            <span class="text-xs text-gray-400">+{{ count($tpl->placeholders) - 4 }} autres</span> @endif
        </div> @endif

        <div class="flex gap-2 mt-auto pt-2 border-t border-gray-100"> <a href="{{ route('admin.pdf-templates.edit', $tpl) }}"
               class="flex-1 text-center text-xs font-medium bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-lg transition"> Modifier
            </a> <a href="{{ route('admin.pdf-templates.preview', $tpl) }}" target="_blank"
               class="flex-1 text-center text-xs font-medium bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-3 py-2 rounded-lg transition"> Aperçu
            </a> </div> </div> @empty
    <div class="col-span-3 text-center text-gray-400 py-16"> Aucun template PDF défini. Lancez le seeder : <code class="text-xs bg-gray-100 px-2 py-0.5 rounded">php artisan db:seed --class=PdfTemplateSeeder</code> </div> @endforelse
</div> @endsection
