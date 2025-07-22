<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntradasTable extends Migration
{
    public function up()
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();

            $table->string('evento')->default('Mi Evento Especial');
            $table->string('codigo_qr')->unique(); // ejemplo: "000"
            $table->string('nombre');
            $table->string('dni');
            $table->date('fecha');
            $table->boolean('valido')->default(true);
            $table->boolean('usada')->default(false);
            $table->string('qr_path'); // path al archivo .png

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entradas');
    }
}
