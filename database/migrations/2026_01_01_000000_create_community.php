<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('member');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('parent_id')->nullable()->constrained('entries');
            $table->string('kind', 24);
            $table->string('title', 150);
            $table->text('body');
            $table->string('area', 50)->nullable();
            $table->string('animal', 30)->nullable();
            $table->string('photo')->nullable();
            $table->string('status', 24)->default('open');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['kind', 'status', 'area']);
            $table->index(['parent_id', 'kind']);
        });
        Schema::create('saved_items', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('entry_id')->constrained();
            $table->primary(['user_id', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_items');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};