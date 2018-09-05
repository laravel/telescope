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
            $table->tinyInteger('type');
            $table->json('content');
            $table->timestamp('created_at');

            $table->index('batch_id');
            $table->index('type');
        });

        Schema::create('telescope_entries_tags', function (Blueprint $table) {
            $table->bigInteger('entry_id');
            $table->string('tag');

            $table->index(['entry_id', 'tag']);
            $table->index('tag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_entries_tags');
    }
}
