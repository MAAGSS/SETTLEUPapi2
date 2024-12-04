<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsLogsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Add user_id column
            $table->string('contact_number');
            $table->string('email')->nullable();
            $table->text('message');
            $table->string('type'); // 'SMS' or 'Email'
            $table->string('status'); // 'Success' or 'Failed'
            $table->text('error_message')->nullable(); // To store errors
            $table->timestamps();

            // Define foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications_logs');
    }
}
