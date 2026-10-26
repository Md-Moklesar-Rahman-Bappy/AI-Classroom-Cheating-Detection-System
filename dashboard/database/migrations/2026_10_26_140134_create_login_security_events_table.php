<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_security_events', function (Blueprint $table) {
            $table->id();
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_ip', 45)->nullable()->index();
            $table->enum('event_type', ['failed_attempt', 'pair_block', 'ip_block', 'unblock', 'trusted_network_add']);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('first_failure')->nullable();
            $table->timestamp('last_failure')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('resolution', 100)->nullable();
            $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['normalized_email', 'normalized_ip']);
            $table->index('blocked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_security_events');
    }
};
