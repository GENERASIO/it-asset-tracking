<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('location');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $locations = Location::orderBy('name')->get();

        return view('users.create', compact('locations'));
    }

    public function store(Request $request)
    {
        // Quick-add dari form "Dipegang Oleh" (dikirim via fetch/JSON) cuma mencatat
        // pemegang aset, bukan akun login - jadi email & password di sana opsional.
        // Form "Kelola User -> Tambah User" (submit halaman biasa) tetap wajib isi
        // keduanya karena itu benar-benar akun yang akan dipakai login.
        $isQuickAdd = $request->wantsJson();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ($isQuickAdd ? 'nullable' : 'required').'|email|unique:users,email',
            'password' => ($isQuickAdd ? 'nullable' : 'required').'|min:8|confirmed',
            'role' => 'required|in:super_admin,it_staff,user',
            'location_id' => 'nullable|exists:locations,id',
            'employee_id' => 'nullable|string|max:255|unique:users,employee_id',
        ]);

        $email = $validated['email'] ?? $this->generatePlaceholderEmail($validated['name']);
        $password = $validated['password'] ?? Str::random(32);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $validated['role'],
            'location_id' => $validated['location_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        if ($request->wantsJson()) {
            return response()->json(['user' => $user->only('id', 'name', 'email')], 201);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $locations = Location::orderBy('name')->get();

        return view('users.edit', compact('user', 'locations'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|in:super_admin,it_staff,user',
            'location_id' => 'nullable|exists:locations,id',
            'employee_id' => 'nullable|string|max:255|unique:users,employee_id,' . $user->id,
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'location_id' => $validated['location_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'is_active' => $user->id === $request->user()->id ? true : $request->boolean('is_active'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    protected function generatePlaceholderEmail(string $name): string
    {
        $slug = Str::slug($name) ?: 'user';

        do {
            $email = $slug.'+'.Str::random(6).'@pengguna-aset.local';
        } while (User::where('email', $email)->exists());

        return $email;
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
