<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\SecureLink;
use App\Services\NotificationService;
use App\Services\SecureLinkService;
use Illuminate\Http\Request;

class SecureLinkController extends Controller
{
    public function __construct(
        private readonly SecureLinkService   $secureLinkService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index()
    {
        $links = SecureLink::with(['hotel', 'creator'])->orderByDesc('created_at')->paginate(20);
        return view('admin.secure-links.index', compact('links'));
    }

    public function create()
    {
        $hotels = Hotel::active()->get();
        return view('admin.secure-links.create', compact('hotels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'agency_name'    => 'required|string|max:255',
            'agency_email'   => 'required|email',
            'contact_name'   => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:30',
            'hotel_id'       => 'nullable|exists:hotels,id',
            'expires_in_days'=> 'nullable|integer|min:1|max:365',
            'max_uses'       => 'nullable|integer|min:1|max:100',
            'notes'          => 'nullable|string|max:500',
            'send_email'     => 'boolean',
        ]);

        $link = $this->secureLinkService->generate($data, $request->user());

        if ($request->boolean('send_email')) {
            $this->notificationService->sendInvitation($link);
        }

        return redirect()
            ->route('admin.secure-links.show', $link)
            ->with('success', "Lien créé" . ($request->boolean('send_email') ? ' et envoyé par email.' : '.'));
    }

    public function show(SecureLink $secureLink)
    {
        $secureLink->load(['hotel', 'creator', 'reservations']);
        return view('admin.secure-links.show', compact('secureLink'));
    }

    public function sendEmail(SecureLink $secureLink)
    {
        $this->notificationService->sendInvitation($secureLink);
        return back()->with('success', 'Email d\'invitation renvoyé.');
    }

    public function revoke(SecureLink $secureLink)
    {
        $this->secureLinkService->revoke($secureLink);
        return back()->with('success', 'Lien révoqué.');
    }

    public function regenerate(SecureLink $secureLink)
    {
        $updated = $this->secureLinkService->regenerate($secureLink);
        return redirect()
            ->route('admin.secure-links.show', $updated)
            ->with('success', 'Token régénéré.');
    }
}
