<?php

namespace Database\Seeders;

use App\Models\Recording;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecordingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all available tags to attach
        $tags = Tag::all();

        // Create 20 random recordings
        Recording::factory(20)->create()->each(function ($recording) use ($tags) {
            // Attach 1 to 3 random tags to each recording, if any tags exist
            if ($tags->count() > 0) {
                $recording->tags()->attach(
                    $tags->random(rand(1, min(3, $tags->count())))->pluck('id')->toArray()
                );
            }
        });
    }
}
