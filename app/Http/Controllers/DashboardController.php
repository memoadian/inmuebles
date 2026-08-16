<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Un Agent sólo ve las métricas de su propio inventario.
        $scope = $user->can('properties.edit-any')
            ? Property::query()
            : Property::query()->ownedBy($user);

        $stats = [
            'total' => (clone $scope)->count(),
            'published' => (clone $scope)->where('status', 'published')->count(),
            'draft' => (clone $scope)->where('status', 'draft')->count(),
            'sold' => (clone $scope)->whereIn('status', ['sold', 'rented'])->count(),
        ];

        $recent = (clone $scope)
            ->with(['type', 'city', 'cover'])
            ->latest()
            ->take(5)
            ->get();

        $usersCount = $user->can('users.view') ? User::count() : null;

        return view('dashboard', compact('stats', 'recent', 'usersCount'));
    }
}
