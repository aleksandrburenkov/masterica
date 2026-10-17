<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'key', 'value', 'type'])]
class Setting extends Model
{
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }
}
