<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $settings = $user->settings()->firstOrCreate([]);

        return Inertia::render('Settings/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $settings->phone,
            ],

            'settings' => [
                'timezone' => $settings->timezone,
                'date_format' => $settings->date_format,
                'language' => $settings->language,
                'email_notifications' => $settings->email_notifications,
                'system_notifications' => $settings->system_notifications,
            ],

            'timezones' => [
                'Africa/Lusaka',
                'Africa/Johannesburg',
                'Africa/Nairobi',
                'Africa/Cairo',
                'Europe/London',
                'Europe/Paris',
                'America/New_York',
                'America/Los_Angeles',
                'Asia/Dubai',
                'Asia/Kolkata',
                'Asia/Singapore',
            ],
        ]);
    }

    /**
     * Update account information.
     */
    public function updateAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->settings()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $validated['phone'] ?? null,
            ]
        );

        return back()->with(
            'success',
            'Account information updated successfully.'
        );
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'timezone' => [
                'required',
                'string',
                'max:100',
                Rule::in(timezone_identifiers_list()),
            ],

            'date_format' => [
                'required',
                'string',
                Rule::in([
                    'd/m/Y',
                    'm/d/Y',
                    'Y-m-d',
                    'd-m-Y',
                    'd.m.Y',
                ]),
            ],

            'language' => [
                'required',
                'string',
                'max:10',
            ],
        ]);

        $request->user()
            ->settings()
            ->updateOrCreate(
                ['user_id' => $request->user()->id],
                $validated
            );

        return back()->with(
            'success',
            'Preferences updated successfully.'
        );
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'email_notifications' => ['required', 'boolean'],
            'system_notifications' => ['required', 'boolean'],
        ]);

        $request->user()
            ->settings()
            ->updateOrCreate(
                ['user_id' => $request->user()->id],
                $validated
            );

        return back()->with(
            'success',
            'Notification preferences updated successfully.'
        );
    }

    /**
     * Change password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            'Password changed successfully.'
        );
    }
}