<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorContactsTable extends Migration
{
    public function up()
    {
        Schema::create('debtor_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_number');
            $table->string('email');
            $table->decimal('credit_score', 5, 2)->default(0);
            $table->foreignId('usr_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        
            // Add unique constraint for email and usr_id
            $table->unique(['email', 'usr_id']);
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('debtor_contacts');
    }
}
