<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin1',
            'password' => bcrypt('123456'),
            'first_name' => 'Jairo',
            'last_name' => 'Alberto',
            'role' => 'admin',
        ]);

        $companyFC = Company::create([
            'name' => 'PedidosAM-FC',
        ]);

        Store::create(['name' => 'FC', 'company_id' => $companyFC->id]);

        $companyOP = Company::create([
            'name' => 'PedidosAM-OP',
        ]);

        Store::create(['name' => 'OP', 'company_id' => $companyOP->id]);
    }
}
