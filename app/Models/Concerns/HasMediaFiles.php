<?php

namespace App\Models\Concerns;

use App\Services\MediaFileService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HasMediaFiles
{
    /**
     * @return list<string>
     */
    public function mediaAttributes(): array
    {
        return ['image'];
    }

    protected static function bootHasMediaFiles(): void
    {
        static::deleted(function (Model $model): void {
            $cleanup = fn () => app(MediaFileService::class)->deleteForModel($model);

            if (DB::transactionLevel() > 0) {
                DB::afterCommit($cleanup);

                return;
            }

            $cleanup();
        });
    }
}
