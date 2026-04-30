<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class GenerateIdNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $items = MenuItem::all();
        foreach ($items as $item) {
            if (!$item->id_number) {
                $item->update(['id_number' => MenuItem::generateIdNumber()]);
            }
        }
        echo "Generated ID numbers for " . $items->count() . " items\n";
    }
}