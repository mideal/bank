<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->timestamps();

            $table->index('transaction_id');
            $table->index(['account_id', 'created_at']);
        });

        DB::statement(<<<'SQL'
            CREATE RULE no_update_entries AS ON UPDATE TO entries DO INSTEAD NOTHING;
        SQL);

        DB::statement(<<<'SQL'
            CREATE RULE no_delete_entries AS ON DELETE TO entries DO INSTEAD NOTHING;
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
