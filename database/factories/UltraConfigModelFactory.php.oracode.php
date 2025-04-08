<?php

namespace Ultra\UltraConfigManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ultra\UltraConfigManager\Models\UltraConfigModel;

class UltraConfigModelFactory extends Factory
{
    protected $model = UltraConfigModel::class;

/**
 * TODO: [UDP] Describe purpose of 'definition'
 *
 * Semantic placeholder auto-inserted by Oracode.
 */
    public function definition()
    {
        return [
            'key' => $this->faker->unique()->word . '.' . $this->faker->word,
            'value' => $this->faker->sentence,
            'category' => $this->faker->randomElement(['system', 'application', 'security']),
            'note' => $this->faker->paragraph(1),
        ];
    }
}