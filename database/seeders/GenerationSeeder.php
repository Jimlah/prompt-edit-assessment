<?php

namespace Database\Seeders;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GenerationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if ($user === null) {
            return;
        }

        $showcase = [
            [
                'prompt' => 'Cyberpunk android portrait with luminous circuitry and a neon city bokeh.',
                'image_url' => '/images/showcase/cyberpunk.webp',
            ],
            [
                'prompt' => 'An ornate mechanical beetle resting on a moss-covered stone, macro 3D render.',
                'image_url' => '/images/showcase/beetle.webp',
            ],
            [
                'prompt' => 'A glass lantern filled with a glowing tree and cyan butterflies in a moonlit forest.',
                'image_url' => '/images/showcase/lantern.webp',
            ],
        ];

        foreach ($showcase as $generation) {
            Generation::factory()->succeeded()->for($user)->create($generation);
        }
    }
}
