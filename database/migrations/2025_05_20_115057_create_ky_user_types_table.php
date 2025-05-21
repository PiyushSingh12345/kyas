<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
      {
          if (!Schema::hasTable('ky_user_types')) {
              Schema::create('ky_user_types', function (Blueprint $table) {
                  $table->bigIncrements('ky_user_type_id');
                  $table->unsignedBigInteger('user_id');
                  $table->unsignedBigInteger('md_user_type_id')->nullable();
                  $table->boolean('is_active')->default(1);
                  $table->timestamp('created_at')->useCurrent();

                  $table->foreign('user_id')->references('user_id')->on('users');
                  $table->foreign('md_user_type_id')->references('md_user_type_id')->on('md_user_types');
              });
          }
      }

      public function down(): void
      {
          Schema::dropIfExists('ky_user_types');
      }
};
