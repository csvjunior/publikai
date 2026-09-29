<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! config('registration.enabled', false)) {
            abort(403);
        }

        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegistrationService $service): RedirectResponse
    {
        $user = $service->register($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
