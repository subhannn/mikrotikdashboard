<?php namespace Xnitro\Mikrotik\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePoolIpsTable extends Migration
{
    public function up()
    {
        Schema::create('xnitro_mikrotik_pool_ips', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xnitro_mikrotik_pool_ips');
    }
}
