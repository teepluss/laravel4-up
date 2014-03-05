<?php

use Illuminate\Database\Migrations\Migration;

class CreateAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attachments', function($table)
		{
			$table->engine = 'InnoDB';

		    $table->string('id', 100)->unique();
		    $table->string('tags', 255)->nullable();
		    $table->string('master', 100)->nullable();
		    $table->string('scale', 100)->nullable();
		    $table->string('path', 255);
		    $table->string('name', 100);
		    $table->string('location', 255);
		    $table->string('size', 100);
		    $table->string('mime', 100);
		    $table->string('dimension', 100)->nullable();
		    $table->integer('order', 100);
		    $table->timestamps();

		    $table->index('master');
		    $table->index('scale');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('attachments');
	}

}