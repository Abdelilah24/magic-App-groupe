@php
$colors = [
    'draft'                => 'bg-gray-100 text-gray-700',
    'pending'              => 'bg-yellow-100 text-yellow-800',
    'accepted'             => 'bg-blue-100 text-blue-800',
    'refused'              => 'bg-red-100 text-red-800',
    'waiting_payment'      => 'bg-orange-100 text-orange-800',
    'paid'                 => 'bg-teal-100 text-teal-800',
    'confirmed'            => 'bg-green-100 text-green-800',
    'modification_pending' => 'bg-purple-100 text-purple-800',
    'cancelled'            => 'bg-red-100 text-red-700',
];
$cls = $colors[$status] ?? 'bg-gray-100 text-gray-700';
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
    {{ $label }}
</span>
