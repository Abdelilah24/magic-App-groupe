<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgencyAuthController extends Controller
{
    public function showLogin()
    {
        if (auth('agency')->check()) {
            return redirect()->route('agency.portal.dashboard');
        }
        return view('agencies.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (auth('agency')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (! auth('agency')->user()->isApproved()) {
                auth('agency')->logout();
                return back()->withErrors(['email' => 'Votre compte est en attente d\'activation.']);
            }

            return redirect()->intended(route('agency.portal.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email ou mot de passe incorrect.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        auth('agency')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('agency.login');
    }
}
