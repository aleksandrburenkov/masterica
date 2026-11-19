<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Сотрудник, которому владелец разрешил получать письма о новых обращениях.
 * Адрес всегда берётся из актуального профиля пользователя.
 */
#[Fillable(['user_id'])]
class NotificationRecipient extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
