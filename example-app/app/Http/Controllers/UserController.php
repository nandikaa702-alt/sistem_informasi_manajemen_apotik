<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = [
        User::ROLE_ADMIN => 'Admin',
        User::ROLE_APOTEKER => 'Apoteker',
        User::ROLE_KASIR => 'Kasir',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create()
    {
        return view('users.create', ['user' => new User(), 'roles' => self::ROLES]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('status', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => self::ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validatedData($request, $user);

        if ($user->role === User::ROLE_ADMIN && $data['role'] !== User::ROLE_ADMIN && $this->adminCount() <= 1) {
            return back()->withInput()->withErrors(['role' => 'Aplikasi harus memiliki setidaknya satu admin.']);
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return redirect()->route('users.index')->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($user->role === User::ROLE_ADMIN && $this->adminCount() <= 1) {
            return redirect()->route('users.index')->with('error', 'Admin terakhir tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Akun pengguna berhasil dihapus.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ];

        if ($user === null) {
            $rules['password'] = ['required', 'string', 'min:10', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:10', 'confirmed'];
        }

        return $request->validate($rules);
    }

    private function adminCount(): int
    {
        return User::where('role', User::ROLE_ADMIN)->count();
    }
}