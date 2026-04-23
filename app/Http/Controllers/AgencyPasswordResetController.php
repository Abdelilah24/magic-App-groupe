<?php

namespace App\Http\Controllers;

use App\Mail\AgencyPasswordResetMail;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgencyPasswordResetController extends Controller
{
    // ─── Formulaire « Mot de passe oublié » ──────────────────────────────────

    public function showForgotForm(): View
    {
        return view('agencies.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $agency = Agency::where('email', $request->email)->first();

        // Always return the same message to avoid user enumeration
        if (! $agency) {
            return back()->with(
                'status',
                'Si un compte existe avec cette adresse, un lien de réinitialisation vous a été envoyé.'
            );
        }

        // Delete any existing token for this email, then insert a fresh one
        DB::table('agency_password_resets')->where('email', $agency->email)->delete();

        $token = Str::random(64);

        DB::table('agency_password_resets')->insert([
            'email'      => $agency->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        $resetUrl = route('agency.password.reset.form', [
            'token' => $token,
            'email' => $agency->email,
        ]);

        Mail::to($agency->email)->send(new AgencyPasswordResetMail($resetUrl, $agency->name));

        return back()->with(
            'status',
            'Si un compte existe avec cette adresse, un lien de réinitialisation vous a été envoyé.'
        );
    }

    // ─── Formulaire de réinitialisation ──────────────────────────────────────

    public function showResetForm(Request $request, string $token): View
    {
        return view('agencies.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'email'                 => ['required', 'email'],
            'token'                 => ['required'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('agency_password_resets')
            ->where('email', $request->email)
            ->first();

        // Token expired after 60 minutes or invalid
        if (
            ! $record
            || ! Hash::check($request->token, $record->token)
            || now()->diffInMinutes($record->created_at) > 60
        ) {
            return back()->withErrors([
                'email' => 'Ce lien de réinitialisation est invalide ou a expiré. Veuillez faire une nouvelle demande.',
            ])->withInput(['email' => $request->email]);
        }

        $agency = Agency::where('email', $request->email)->first();

        if (! $agency) {
            return back()->withErrors(['email' => 'Aucun compte trouvé.']);
        }

        $agency->update(['password' => $request->password]);

        // Clean up used token
        DB::table('agency_password_resets')->where('email', $request->email)->delete();

        return redirect()->route('agency.login')->with(
            'status',
            'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.'
        );
    }
}
