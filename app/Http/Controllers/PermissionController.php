<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('permissions.view'), 403);

        return view('permissions.index', [
            'groups' => Permission::withCount('roles')->orderBy('name')->get()->groupBy('group'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('permissions.create'), 403);

        return view('permissions.create', ['groups' => $this->existingGroups()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('permissions.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'group' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Permission::create([...$data, 'guard_name' => 'web']);

        return redirect()->route('permissions.index')->with('success', 'Permiso creado.');
    }

    public function edit(Request $request, Permission $permission)
    {
        abort_unless($request->user()->can('permissions.edit'), 403);

        return view('permissions.edit', [
            'permission' => $permission,
            'groups' => $this->existingGroups(),
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        abort_unless($request->user()->can('permissions.edit'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'group' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission->update($data);

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado.');
    }

    public function destroy(Request $request, Permission $permission)
    {
        abort_unless($request->user()->can('permissions.delete'), 403);

        if ($permission->roles()->exists()) {
            return back()->with('error', 'No se puede eliminar: hay roles usando este permiso.');
        }

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado.');
    }

    private function existingGroups()
    {
        return Permission::query()->distinct()->orderBy('group')->pluck('group')->filter();
    }
}
