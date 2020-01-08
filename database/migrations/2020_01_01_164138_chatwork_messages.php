<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChatworkMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chatwork_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('message_id', 20);
            $table->integer('room_id');
            $table->integer('account_id');
            $table->text('body');
            $table->string('account_name', 100);
            $table->string('task_id', 50);
            $table->string('project_name', 100);
            $table->string('task_url', 191);
            $table->integer('start_time');
            $table->integer('end_time');
            $table->string('task_status', 10);
            $table->integer('update_time');
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
        Schema::drop('chatwork_messages');
    }
}
