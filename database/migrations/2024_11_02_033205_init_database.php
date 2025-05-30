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
    Schema::table('users', function (Blueprint $table) {
      $table->string('location')->nullable();
      $table->string('city')->nullable();
      $table->string('avatar')->nullable();
      $table->string('banner')->nullable();
      $table->string('overview')->nullable();
      $table->integer('online_status')->default(1);
      $table->integer('status')->default(1);
    });
    Schema::create('follows', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('follow_id');
      $table->timestamps();
    });
    Schema::create('notifications', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('action_id');
      $table->string('content')->nullable();
      $table->integer('is_view')->default(1);
      $table->string('status')->default('wait');
      $table->timestamps();
    });
    Schema::create('friends', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('friend_id');
      $table->integer('approved')->default(1);
      $table->timestamps();
    });
    Schema::create('violence_logs', function (Blueprint $table) {
      $table->id();
      $table->integer('message_id')->default(1);
      $table->integer('post_id')->default(1);
      $table->integer('comment_id')->default(1);
      $table->timestamps();
    });
    Schema::create('user_views', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('view_id');
      $table->timestamps();
    });
    Schema::create('messages', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('friend_id');
      $table->string('message', 255)->nullable();
      $table->integer('is_view')->default(1);
      $table->timestamps();
    });
    Schema::create('experiences', function (Blueprint $table) {
      $table->id();
      $table->string('title', 255)->nullable();
      $table->string('description', 255)->nullable();
      $table->integer('user_id');
      $table->timestamps();
    });
    Schema::create('likes', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('post_id');
      $table->timestamps();
    });
    Schema::create('violence_warnings', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('infringe')->default(0);
      $table->timestamps();
    });
    Schema::create('skills', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->string('skill', 255)->nullable();
      $table->timestamps();
    });
    Schema::create('posts', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->string('content', 255)->nullable();
      $table->timestamp('timeline_orders')->nullable();
      $table->integer('view_count')->default(0);
      $table->text('images')->nullable(); //multiple images saved as json array
      $table->timestamps();
    });
    Schema::create('comments', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->integer('post_id');
      $table->string('comment', 255);
      $table->integer('parent_id')->nullable();
      $table->timestamps();
    });
    Schema::create('device_tokens', function (Blueprint $table) {
      $table->id();
      $table->integer('user_id');
      $table->string('token', 255)->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    //
  }
};
