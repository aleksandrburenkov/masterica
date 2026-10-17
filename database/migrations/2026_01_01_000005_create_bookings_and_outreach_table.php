<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_session_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('held')->index();
            $table->string('payment_status', 32)->default('unpaid')->index();
            $table->unsignedInteger('participants_count')->default(1);
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('contact_email');
            $table->unsignedInteger('child_age')->nullable();
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'workshop_session_id'], 'bookings_user_session_unique');
            $table->index(['workshop_session_id', 'status']);
        });

        Schema::create('outreach_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->date('requested_date');
            $table->string('location');
            $table->unsignedInteger('participants_count');
            $table->string('age_group')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('contact_email');
            $table->unsignedInteger('base_price_cents')->default(0);
            $table->unsignedInteger('included_participants')->default(8);
            $table->unsignedInteger('extra_count')->default(0);
            $table->unsignedInteger('extra_price_cents')->default(0);
            $table->unsignedInteger('calc_total_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->string('status', 32)->default('new')->index();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_requests');
        Schema::dropIfExists('bookings');
    }
};
