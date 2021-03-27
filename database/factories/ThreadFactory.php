<?php

namespace Database\Factories;

use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence(2, true);
        return [
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'body' => $this->faker->realText(100),
            'user_id' => rand(1, 10),
            'channel_id' => rand(1, 5)
        ];
    }
}
