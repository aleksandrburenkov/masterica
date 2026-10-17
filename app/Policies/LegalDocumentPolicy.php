<?php

namespace App\Policies;

use App\Models\LegalDocument;
use App\Models\User;

/**
 * Правовые тексты редактирует только владелец (роль owner).
 */
class LegalDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageSettings();
    }

    public function view(User $user, LegalDocument $document): bool
    {
        return $user->role->canManageSettings();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageSettings();
    }

    public function update(User $user, LegalDocument $document): bool
    {
        return $user->role->canManageSettings();
    }

    public function delete(User $user, LegalDocument $document): bool
    {
        return $user->role->canManageSettings();
    }
}
