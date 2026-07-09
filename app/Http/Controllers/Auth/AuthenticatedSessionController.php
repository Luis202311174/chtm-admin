<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $supabaseUrl = rtrim(config('services.supabase.url'), '/');
        $supabaseApiKey = config('services.supabase.key');

        if (!$supabaseUrl || !$supabaseApiKey) {
            Log::error('Supabase configuration missing: SUPABASE_URL or SUPABASE_KEY not set.');
            throw ValidationException::withMessages([
                'email' => 'Server configuration error. Contact administrator.',
            ]);
        }

        Log::info('Authenticating with Supabase...', ['email' => $request->email]);

        // Authenticate against Supabase Auth
        $response = Http::withHeaders([
            'apikey'        => $supabaseApiKey,
            'Content-Type'  => 'application/json',
        ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if ($response->failed()) {
            Log::warning('Supabase Auth rejected credentials', ['status' => $response->status()]);
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $authData = $response->json();
        $supabaseUid = $authData['user']['id'] ?? null;
        $supabaseEmail = $authData['user']['email'] ?? $request->email;

        if (!$supabaseUid) {
            Log::error('Supabase Auth succeeded but missing user ID.', ['response' => $authData]);
            throw ValidationException::withMessages([
                'email' => 'Authentication failed unexpectedly.',
            ]);
        }

        Log::info('Supabase Auth successful. Syncing local user.', ['supabase_uid' => $supabaseUid]);

        // Find or create the user locally with direct DB insert to bypass cast issues
        $user = User::find($supabaseUid);

        if (!$user) {
            // Create user directly using query builder to avoid encryption cast interference
            $now = now();
            \Illuminate\Support\Facades\DB::table('users')->insert([
                'id'         => $supabaseUid,
                'fname'      => 'CHTM',
                'lname'      => 'FRONT OFFICE',
                'email'      => $supabaseEmail,
                'role'       => 'frontoffice',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $user = User::find($supabaseUid);
        }

        if (!$user) {
            Log::error('Failed to create/find user after Supabase auth.', ['supabase_uid' => $supabaseUid]);
            throw ValidationException::withMessages([
                'email' => 'Unable to establish local session.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->put('supabase_token', $authData['access_token'] ?? null);
        $request->session()->put('supabase_refresh_token', $authData['refresh_token'] ?? null);
        $request->session()->regenerate();

        $destination = match ((string) $user->role) {
            'reservation' => route('reservation'),
            'housekeeper' => route('room', ['tab' => 'housekeeping']),
            'admin'       => route('room', ['tab' => 'inventory']),
            default       => route('dashboard'),
        };

        Log::info('Login success. Redirecting to: ' . $destination);

        return redirect()->intended($destination);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}