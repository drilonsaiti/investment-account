<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('type', array_column(\App\Enums\TransactionType::cases(), 'value'));
            $table->decimal('amount', 15, 2);
            $table->string('instrument')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('price_per_unit', 15, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['client_id', 'instrument']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
