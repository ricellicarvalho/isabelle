<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('alert_type');
            $table->unsignedSmallInteger('days_before');
            $table->date('sent_date');
            $table->timestamps();

            $table->unique(
                ['notifiable_type', 'notifiable_id', 'alert_type', 'days_before', 'sent_date'],
                'notification_logs_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
