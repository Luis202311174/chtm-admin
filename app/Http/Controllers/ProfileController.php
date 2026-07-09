<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile', [
            'title' => 'Profile',
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $email = $request->string('email')->toString();
        $emailHash = hash('sha256', $email);
        
        if (User::query()->where('email_hash', $emailHash)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['email' => 'Email already in use.'])->withInput();
        }

        $user->fill($request->validated());
        $user->email_hash = $emailHash;
        $user->save();

        return redirect()->route('profile')->with('status', 'Profile updated.');
    }
}