<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Roles del sistema: se pueden editar permisos, pero no borrar ni renombrar. */
    private const PROTECTED_ROLES = ['Admin'];

    public function index(Request $request)
    {
        abort_unless($request->user()->can('roles.view'), 403);

        return view('roles.index', [
            'roles' => Role::withCount(['permissions', 'users'])->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('roles.create'), 403);

        return view('roles.create', ['groups' => $this->permissionGroups()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('roles.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Rol creado.');
    }

    public function show(Request $request, Role $role)
    {
        abort_unless($request->user()->can('roles.view'), 403);

        $role->load('permissions', 'users');

        return view('roles.show', compact('role'));
    }

    public function edit(Request $request, Role $role)
    {
        abort_unless($request->user()->can('roles.edit'), 403);

        return view('roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($request->user()->can('roles.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        // Renombrar un rol del sistema rompería los checks por nombre en el código.
        if (in_array($role->name, self::PROTECTED_ROLES, true) && $data['name'] !== $role->name) {
            return back()->with('error', "El rol {$role->name} no se puede renombrar.");
        }

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado.');
    }

    public function destroy(Request $request, Role $role)
    {
        abort_unless($request->user()->can('roles.delete'), 403);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->with('error', "El rol {$role->name} no se puede eliminar.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'No se puede eliminar: hay usuarios con este rol.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    /** Permisos agrupados por su columna 'group', para pintar el formulario. */
    private function permissionGroups()
    {
        return Permission::orderBy('name')->get()->groupBy('group');
    }
}
