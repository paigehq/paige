<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->word().'.pdf';

        return [
            'page_id' => Page::factory(),
            'filename' => $filename,
            'disk_path' => 'attachments/'.$filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1024, 10485760),
            'uploader_id' => User::factory(),
        ];
    }
}
