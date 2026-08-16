<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('users.view'), 403);

        $users = User::with('roles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->value();
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('users.create'), 403);

        return view('users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('users.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $user = User::create([
            ...collect($data)->except('roles')->all(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->syncRoles($data['roles']);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function show(Request $request, User $user)
    {
        abort_unless($request->user()->can('users.view'), 403);

        $user->load('roles', 'properties');

        return view('users.show', compact('user'));
    }

    public function edit(Request $request, User $user)
    {
        abort_unless($request->user()->can('users.edit'), 403);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->can('users.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
        ]);

        $payload = collect($data)->except(['roles', 'password'])->all();
        $payload['is_active'] = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->syncRoles($data['roles']);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->can('users.delete'), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}
