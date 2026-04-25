<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Afficher la page de profil.
     */
    public function edit()
    {
        $adminEmail = AppSetting::get(AppSetting::KEY_ADMIN_EMAIL, config('mail.from.address'));
        $appLogo    = AppSetting::get(AppSetting::KEY_APP_LOGO);

        return view('admin.profile.edit', compact('adminEmail', 'appLogo'));
    }

    /**
     * Mettre à jour le mot de passe de l'administrateur connecté.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required'  => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required'          => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed'         => 'La confirmation ne correspond pas.',
            'password.min'               => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record('updated', 'Mon profil', 'Mot de passe modifié', [
            'user' => Auth::user()->name,
        ]);

        return back()->with('success_password', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Mettre à jour l'adresse e-mail du compte administrateur connecté.
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email'            => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['required', 'current_password'],
        ], [
            'email.required'          => "L'adresse e-mail est obligatoire.",
            'email.email'             => "L'adresse e-mail n'est pas valide.",
            'email.unique'            => "Cette adresse e-mail est déjà utilisée.",
            'current_password.required'          => 'Le mot de passe actuel est obligatoire.',
            'current_password.current_password'  => 'Le mot de passe actuel est incorrect.',
        ]);

        $oldEmail = $user->email;
        $user->update(['email' => $request->email]);

        ActivityLog::record('updated', 'Mon profil', 'Adresse e-mail modifiée', [
            'old' => $oldEmail,
            'new' => $request->email,
        ]);

        return back()->with('success_email', 'Adresse e-mail mise à jour avec succès.');
    }

    /**
     * Mettre à jour l'email administratif et les paramètres généraux.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'admin_email' => ['required', 'email', 'max:255'],
        ], [
            'admin_email.required' => "L'adresse e-mail est obligatoire.",
            'admin_email.email'    => "L'adresse e-mail n'est pas valide.",
        ]);

        AppSetting::set(AppSetting::KEY_ADMIN_EMAIL, $request->admin_email);

        return back()->with('success_settings', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Mettre à jour le logo de l'application.
     */
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ], [
            'logo.required' => 'Veuillez sélectionner un fichier image.',
            'logo.image'    => 'Le fichier doit être une image.',
            'logo.mimes'    => 'Format accepté : PNG, JPG, SVG ou WebP.',
            'logo.max'      => 'Le fichier ne doit pas dépasser 2 Mo.',
        ]);

        // Supprimer l'ancien logo si présent
        $oldLogo = AppSetting::get(AppSetting::KEY_APP_LOGO);
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Stocker le nouveau logo
        $path = $request->file('logo')->store('logo', 'public');

        AppSetting::set(AppSetting::KEY_APP_LOGO, $path);

        ActivityLog::record('updated', 'Mon profil', 'Logo de l\'application mis à jour');

        return back()->with('success_logo', 'Logo mis à jour avec succès.');
    }

    /**
     * Supprimer le logo (revenir au logo texte par défaut).
     */
    public function deleteLogo()
    {
        $oldLogo = AppSetting::get(AppSetting::KEY_APP_LOGO);
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        AppSetting::remove(AppSetting::KEY_APP_LOGO);

        return back()->with('success_logo', 'Logo supprimé. Le logo par défaut est rétabli.');
    }
}
