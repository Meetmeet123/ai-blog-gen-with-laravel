<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE blogs MODIFY featured_img_path TEXT NULL');
        DB::statement('ALTER TABLE blogs MODIFY middle_img_path TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE blogs MODIFY featured_img_path VARCHAR(255) NULL');
        DB::statement('ALTER TABLE blogs MODIFY middle_img_path VARCHAR(255) NULL');
    }
};
