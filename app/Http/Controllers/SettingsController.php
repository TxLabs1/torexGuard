<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.profile');
    }

    public function profile()
    {
        return view('settings.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.Auth::id(),
            'timezone' => 'nullable|string',
        ]);
        Auth::user()->update($validated);

        return redirect()->back()->with('success', 'Profile updated');
    }

    public function organization()
    {
        $org = Auth::user()->organizations()->first();

        return view('settings.organization', compact('org'));
    }

    public function updateOrganization(Request $request)
    {
        $org = Auth::user()->organizations()->first();
        $validated = $request->validate(['name' => 'required|string|max:100', 'email' => 'nullable|email']);
        $org->update($validated);

        return redirect()->back()->with('success', 'Organization updated');
    }

    public function notifications()
    {
        $org = Auth::user()->organizations()->first();
        $settings = $org->notificationSettings ?? new NotificationSetting;

        return view('settings.notifications', compact('settings'));
    }

    public function updateNotifications(Request $request)
    {
        $org = Auth::user()->organizations()->first();
        NotificationSetting::updateOrCreate(
            ['organization_id' => $org->id],
            $request->only(['email_enabled', 'slack_enabled', 'slack_webhook', 'webhook_enabled', 'webhook_url'])
        );

        return redirect()->back()->with('success', 'Notification settings saved');
    }

    public function apiTokens()
    {
        $org = Auth::user()->organizations()->first();
        $tokens = ApiToken::where('organization_id', $org->id)
            ->where('user_id', Auth::id())->get();

        return view('settings.api-tokens', compact('tokens'));
    }

    public function createToken(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $org = Auth::user()->organizations()->first();
        abort_if(! $org, 422, 'No organization for current user.');

        $token = new ApiToken([
            'user_id' => Auth::id(),
            'organization_id' => $org->id,
            'name' => $data['name'],
            'is_active' => true,
        ]);
        $token->save();

        $plaintext = $token->issueToken();

        return redirect()->back()
            ->with('new_token', $plaintext)
            ->with('success', "Token created — copy it now, it won't be shown again");
    }

    public function revokeToken(ApiToken $token)
    {
        $token->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Token revoked');
    }
}
