<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        if (Message::exists()) {
            return;
        }
        Message::factory(6)->sequence(fn ($s) => ['branch_id' => Branch::inRandomOrder()->value('id')])->create();
    }
}
