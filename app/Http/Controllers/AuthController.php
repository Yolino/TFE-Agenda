<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function index(): View
    {
        return view('auth.index');
    }

    public function login(LoginRequest $loginRequest): RedirectResponse
    {
        $credentials = $loginRequest->validated();

        if (Auth::attempt($credentials)) {
            $loginRequest->session()->regenerate();
            // Redirect to 'dashboard' if the intended route is not defined
            return redirect()->intended(route('dashboard'))->with('success', 'Vous êtes connecté.');
        }

        return to_route('auth.index')->withErrors(['errorsCredentials' => 'Les identifiants sont incorrectes.'])->onlyInput('email');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        return to_route('auth.index');
    }
}
