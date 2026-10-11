<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pages edited with the GrapesJS visual editor keep their project (JSON), the published HTML and CSS. */
return new class extends Migration {
    private function table(): string
    {
        return (config('pagebuilder.db_prefix') ?? 'pagebuilder__') . 'pages';
    }

    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->longText('gjs_project')->nullable();
            $table->longText('gjs_html')->nullable();
            $table->longText('gjs_css')->nullable();
            $table->timestamp('edited_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->dropColumn(['gjs_project', 'gjs_html', 'gjs_css', 'edited_at']);
        });
    }
};
