<?php namespace Xnitro\Mikrotik\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateMetaIpsTable extends Migration
{
    public function up()
    {
        Schema::create('xnitro_mikrotik_meta_ips', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xnitro_mikrotik_meta_ips');
    }
}
