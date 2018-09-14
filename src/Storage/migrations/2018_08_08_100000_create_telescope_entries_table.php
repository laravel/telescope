<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelescopeEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('batch_id');
            $table->string('type', 20);
            $table->json('content');
            $table->timestamp('created_at');

            $table->index('batch_id');
            $table->index('type');
        });

        Schema::create('telescope_entries_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('entry_id');
            $table->string('tag');

            $table->index(['entry_id', 'tag']);
            $table->index('tag');

            $table->foreign('entry_id')
                  ->references('id')
                  ->on('telescope_entries')
                  ->onDelete('cascade');
        });

        Schema::create('telescope_monitoring', function (Blueprint $table) {
            $table->string('tag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');
    }
}
