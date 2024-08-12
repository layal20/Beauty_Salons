<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('logo_image')->unique();
            $table->string('description');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->decimal('latitude', 10, 7)->nullable()->unique();
            $table->decimal('longitude', 10, 7)->nullable()->unique();
            $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();
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
        Schema::dropIfExists('salons');
    }
};
