<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/** Nạp chi nhánh và phòng từ data/catalog/*.json (chạy lại nhiều lần an toàn). */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $dir = env('CATALOG_DIR', base_path('../data/catalog'));

        foreach (json_decode(File::get("{$dir}/branches.json"), true) as $b) {
            Branch::updateOrCreate(['code' => $b['code']], [
                'name' => $b['name'],
                'address' => $b['address'],
                'phone' => $b['phone'] ?: null,
                'open_time' => $b['openTime'],
                'close_time' => $b['closeTime'],
                'sort_order' => $b['sortOrder'],
            ]);
        }

        $branches = Branch::pluck('id', 'code');
        foreach (json_decode(File::get("{$dir}/rooms.json"), true) as $r) {
            Room::updateOrCreate(
                ['branch_id' => $branches[$r['branchCode']], 'name' => $r['name']],
                ['style' => $r['style'], 'description' => $r['description'], 'price_per_slot' => $r['pricePerSlot'], 'sort_order' => $r['sortOrder']],
            );
        }
    }
}
