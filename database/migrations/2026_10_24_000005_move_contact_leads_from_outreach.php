<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ранее форма контактов сохранялась в outreach_requests с направлением
 * «Вопрос со страницы контактов». Переносим такие обращения в contact_requests,
 * чтобы раздел «Выездные мастер-классы» содержал только выездные заявки.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $legacy = DB::table('outreach_requests')
                ->where('direction', 'Вопрос со страницы контактов')
                ->get();

            foreach ($legacy as $row) {
                DB::table('contact_requests')->insert([
                    'number' => 'CT-LEGACY-'.$row->id,
                    'user_id' => $row->user_id,
                    'contact_name' => $row->contact_name,
                    'contact_phone' => $row->contact_phone,
                    'contact_email' => $row->contact_email,
                    'message' => filled($row->comment)
                        ? $row->comment
                        : 'Обращение со страницы контактов',
                    'status' => $row->status,
                    'consent_data' => true,
                    'consent_marketing' => false,
                    'source' => 'kontakty',
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                DB::table('outreach_requests')->where('id', $row->id)->delete();
            }
        });
    }

    public function down(): void
    {
        // Обратного переноса нет: данные остаются в contact_requests.
    }
};
