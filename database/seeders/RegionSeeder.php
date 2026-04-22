<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Provinces...');
        $provinces = json_decode(file_get_contents(base_path('json/propinsi.json')), true);
        foreach ($provinces['propinsi'] as $p) {
            \DB::table('provinces')->insert(['id' => $p['id'], 'name' => $p['nama']]);
        }

        $this->command->info('Seeding Regencies...');
        $regencies = json_decode(file_get_contents(base_path('json/kabupaten.json')), true);
        foreach ($regencies['kabupaten'] as $k) {
            \DB::table('regencies')->insert(['id' => $k['id'], 'province_id' => $k['id_propinsi'], 'name' => $k['nama']]);
        }

        $this->command->info('Seeding Districts...');
        $districts = json_decode(file_get_contents(base_path('json/kecamatan.json')), true);
        foreach ($districts['kecamatan'] as $kc) {
            \DB::table('districts')->insert(['id' => $kc['id'], 'regency_id' => $kc['id_kabupaten'], 'name' => $kc['nama']]);
        }

        $this->command->info('Seeding Villages...');
        $villages = json_decode(file_get_contents(base_path('json/kelurahan.json')), true);
        // Chunking village as it's large
        $villageData = [];
        foreach ($villages['kelurahan'] as $kl) {
            $villageData[] = [
                'id' => $kl['id'],
                'district_id' => $kl['id_kecamatan'],
                'name' => $kl['nama']
            ];

            if (count($villageData) >= 1000) {
                \DB::table('villages')->insert($villageData);
                $villageData = [];
            }
        }
        if (!empty($villageData)) {
            \DB::table('villages')->insert($villageData);
        }
    }
}
