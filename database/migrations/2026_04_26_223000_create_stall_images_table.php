<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (!Schema::hasColumn('stalls', 'stall_image_path')) {
            return;
        }

        $existingImages = DB::table('stalls')
            ->whereNotNull('stall_image_path')
            ->where('stall_image_path', '!=', '')
            ->orderBy('id')
            ->get(['id', 'stall_image_path']);

        if ($existingImages->isEmpty()) {
            return;
        }

        $timestamp = now();

        DB::table('stall_images')->insert(
            $existingImages->map(function ($stall) use ($timestamp): array {
                return [
                    'stall_id' => $stall->id,
                    'image_path' => $stall->stall_image_path,
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_images');
    }
};
