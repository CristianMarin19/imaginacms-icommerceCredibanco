<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIcommerceCredibancoTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('icommercecredibanco__transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            // Your fields
            $table->integer('order_id');
            $table->integer('order_status');
            $table->tinyInteger('type')->default(1)->unsigned();
           
            $table->integer('commerceId')->unsigned();
            $table->dateTime('operationDate');
            $table->string('terminalCode');
            $table->string('operationNumber');
            $table->string('currency');
            $table->double('amount',15,8);
            $table->float('tax',8,2);
            $table->text('description')->default('');

            $table->integer('errorCode')->unsigned()->nullable();
            $table->text('errorMessage')->default('')->nullable();
            
            $table->integer('authorizationCode')->unsigned()->nullable();
            $table->integer('authorizationResult')->unsigned();

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
        Schema::dropIfExists('icommercecredibanco__transactions');
    }
}
