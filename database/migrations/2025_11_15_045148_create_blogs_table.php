<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('selected_intro')->nullable();
            $table->text('content')->nullable();
            $table->string('featured_img_path')->nullable();
            $table->string('middle_img_path')->nullable();
            $table->enum('status', ['draft', 'active', 'published', 'deleted'])->default('draft');
            $table->json('ai_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogs');
    }
}
