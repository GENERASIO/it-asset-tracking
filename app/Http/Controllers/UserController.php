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

    public function create(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        $roles = $this->assignableRoles($request);

        return view('users.create', compact('locations', 'roles'));
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
            'role' => 'required|in:'.implode(',', $this->assignableRoles($request)),
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

    public function edit(Request $request, User $user)
    {
        $this->ensureCanManage($request, $user);

        $locations = Location::orderBy('name')->get();
        $roles = $this->assignableRoles($request);

        return view('users.edit', compact('user', 'locations', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManage($request, $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            // Role akun sendiri tidak bisa diubah, jadi tidak perlu divalidasi.
            'role' => $user->id === $request->user()->id
                ? 'nullable'
                : 'required|in:'.implode(',', $this->assignableRoles($request)),
            'location_id' => 'nullable|exists:locations,id',
            'employee_id' => 'nullable|string|max:255|unique:users,employee_id,' . $user->id,
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Role akun sendiri tidak bisa diubah (cegah menaikkan diri jadi super_admin).
            'role' => $user->id === $request->user()->id ? $user->role : $validated['role'],
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

    /**
     * Role yang boleh diberikan oleh user yang sedang login. IT Staff tidak boleh
     * membuat / menaikkan siapa pun menjadi super_admin.
     */
    protected function assignableRoles(Request $request): array
    {
        return $request->user()->role === 'super_admin'
            ? ['super_admin', 'it_staff', 'user']
            : ['it_staff', 'user'];
    }

    /** Akun super_admin hanya boleh diubah / dihapus oleh super_admin. */
    protected function ensureCanManage(Request $request, User $user): void
    {
        abort_if(
            $user->role === 'super_admin' && $request->user()->role !== 'super_admin',
            403,
            'Hanya Super Admin yang bisa mengubah akun Super Admin.'
        );
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
        $this->ensureCanManage($request, $user);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
