<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Settings', [
            'title' => 'System Settings',
            'activeTab' => $request->string('tab', 'notifications')->toString(),
            'notifications' => $request->session()->get('settings.notifications', [
                'checkIns' => true, 'checkOuts' => true, 'reservations' => true, 'ratings' => true,
            ]),
            'appearance' => $request->session()->get('settings.appearance', ['darkMode' => false]),
            'twoFactorEnabled' => $request->session()->get('settings.two_factor', true),
            'loginAlertEnabled' => $request->session()->get('settings.login_alert', true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $request->string('tab', 'notifications')->toString();
        if ($tab === 'notifications') {
            $request->session()->put('settings.notifications', [
                'checkIns' => $request->boolean('checkIns'),
                'checkOuts' => $request->boolean('checkOuts'),
                'reservations' => $request->boolean('reservations'),
                'ratings' => $request->boolean('ratings'),
            ]);
        }
        if ($tab === 'appearance') {
            $request->session()->put('settings.appearance', ['darkMode' => $request->boolean('darkMode')]);
        }
        if ($tab === 'admin') {
            $request->session()->put('settings.two_factor', $request->boolean('twoFactorEnabled'));
            $request->session()->put('settings.login_alert', $request->boolean('loginAlertEnabled'));
        }
        return redirect()->to(route('settings', ['tab' => $tab]))->with('status', 'Settings saved successfully.');
    }
}