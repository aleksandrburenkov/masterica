<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Отдельный блок заявок на мастер-классы удалён: записи на мастер-классы
 * ведутся в bookings («Заявки → Мастер-классы»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('masterclass_requests');
    }

    public function down(): void
    {
        // Таблица не восстанавливается: данные дублировали bookings.
    }
};
