<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Abandoned-cart reminders: how many were sent for a cart line and when, plus a per-user opt-out. */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('cart_items', 'reminded_at')) {
            Schema::table('cart_items', function (Blueprint $t) {
                $t->timestamp('reminded_at')->nullable();
                $t->unsignedTinyInteger('reminders_count')->default(0);
            });
        }
        if (!Schema::hasColumn('users', 'cart_reminders_off_at')) {
            Schema::table('users', fn (Blueprint $t) => $t->timestamp('cart_reminders_off_at')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('cart_items', fn (Blueprint $t) => $t->dropColumn(['reminded_at', 'reminders_count']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('cart_reminders_off_at'));
    }
};
