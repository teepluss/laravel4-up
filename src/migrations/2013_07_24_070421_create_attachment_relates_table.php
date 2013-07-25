<?php

use Illuminate\Database\Migrations\Migration;

class CreateAttachmentRelatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attachment_relates', function($table)
		{
			$table->engine = 'InnoDB';

		    $table->increments('id');
		    $table->string('attachment_id', 100);
		    $table->string('fileable_type', 100);
		    $table->integer('fileable_id')->unsigned();

		    $table->index('attachment_id');
		    $table->index(array('fileable_id', 'fileable_type'));

		    $table->foreign('attachment_id')
      			  ->references('id')->on('attachments')
      			  ->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropForeign('attachment_relates_attachment_id_foreign');
		Schema::dropIfExists('attachment_relates');
	}

}