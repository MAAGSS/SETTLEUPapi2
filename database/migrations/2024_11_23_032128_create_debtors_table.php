<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debtors', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('usr_id'); // Foreign key column
            $table->foreign('usr_id')->references('id')->on('users')->onDelete('cascade'); // Foreign key constraint
            $table->string('name'); // Debtor's name
            $table->string('contact_number', 15); // Debtor's contact number
            $table->string('email')->unique(); // Debtor's email
            $table->decimal('amount_to_borrow', 10, 2); // Amount to borrow
            $table->date('start_date');
            $table->date('due_date');
            $table->decimal('interest_rate', 5, 2);
            $table->boolean('is_archived')->default(false);
            $table->enum('debt_type', ['receivable', 'payable'])->default('receivable');
            $table->timestamps(); // Created at and Updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debtors');
    }
}
