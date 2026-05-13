<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'organization_name' => 'required|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        $org = Organization::create([
            'name' => $validated['organization_name'],
            'slug' => Str::slug($validated['organization_name']).'-'.Str::random(6),
            'plan' => 'free',
        ]);

        $org->users()->attach($user->id, ['role' => 'owner']);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to Torex Guard!');
    }
}
