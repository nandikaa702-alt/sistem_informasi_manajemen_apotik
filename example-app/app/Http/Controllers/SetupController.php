<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    public function create()
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
    }

    public function store(Request $request)
    {
        if (User::exists()) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_ADMIN,
        ]);

        return redirect()->route('login')->with('status', 'Akun admin berhasil dibuat. Silakan masuk.');
    }
}