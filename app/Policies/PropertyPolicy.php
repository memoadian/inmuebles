<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->can('properties.view') && $this->owns($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->can('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('properties.edit') && $this->owns($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->can('properties.delete') && $this->owns($user, $property);
    }

    public function publish(User $user, Property $property): bool
    {
        return $user->can('properties.publish') && $this->owns($user, $property);
    }

    public function uploadImages(User $user, Property $property): bool
    {
        return $user->can('images.upload') && $this->owns($user, $property);
    }

    public function deleteImages(User $user, Property $property): bool
    {
        return $user->can('images.delete') && $this->owns($user, $property);
    }

    public function reorderImages(User $user, Property $property): bool
    {
        return $user->can('images.reorder') && $this->owns($user, $property);
    }

    /**
     * Un Agent sólo toca lo suyo; quien tenga 'properties.edit-any' (Admin)
     * pasa siempre. Ésta es la única regla que separa a ambos roles.
     */
    private function owns(User $user, Property $property): bool
    {
        return $user->can('properties.edit-any') || $property->user_id === $user->id;
    }
}
