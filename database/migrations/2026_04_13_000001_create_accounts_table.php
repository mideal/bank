<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->decimal('balance', 15, 2)->default('0.00');
            $table->decimal('hold', 15, 2)->default('0.00');
            $table->char('currency', 3)->default('RUB');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE accounts ADD CONSTRAINT chk_accounts_balance CHECK (balance >= 0)');
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT chk_accounts_hold CHECK (hold >= 0)');
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT chk_accounts_available CHECK (balance >= hold)');
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
