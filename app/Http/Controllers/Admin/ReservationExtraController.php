<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationExtra;
use App\Models\ExtraService;
use Illuminate\Http\Request;

class ReservationExtraController extends Controller
{
    public function store(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'extra_service_id' => ['nullable', 'exists:extra_services,id'],
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'notes'            => ['nullable', 'string'],
        ], [
            'name.required'       => 'Le nom du service est obligatoire.',
            'unit_price.required' => 'Le prix unitaire est obligatoire.',
            'unit_price.numeric'  => 'Le prix doit être un nombre.',
            'quantity.required'   => 'La quantité est obligatoire.',
            'quantity.integer'    => 'La quantité doit être un entier.',
            'quantity.min'        => 'La quantité doit être au moins 1.',
        ]);

        $data['total_price'] = round($data['unit_price'] * $data['quantity'], 2);

        $reservation->extras()->create($data);

        // Répercuter sur le total de la réservation
        $reservation->increment('total_price', $data['total_price']);

        return back()->with('success_extras', 'Service extra ajouté à la réservation.');
    }

    public function destroy(Reservation $reservation, ReservationExtra $extra)
    {
        abort_if($extra->reservation_id !== $reservation->id, 403);

        $amount = $extra->total_price;
        $extra->delete();

        // Déduire du total de la réservation (sans descendre sous 0)
        $reservation->decrement('total_price', min($amount, $reservation->total_price));

        return back()->with('success_extras', 'Service extra supprimé.');
    }
}
