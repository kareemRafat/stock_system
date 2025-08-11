<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\InvoiceItem;
use App\Models\CustomerWallet;
use App\Models\OutsourcedProduction;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'كريم',
            'username' => 'kareem',
            'password' => bcrypt('12345678'),
            'role' => 'admin'
        ]);

        User::factory()->create([
            'name' => 'ايمان',
            'username' => 'eman',
            'password' => bcrypt('12345678'),
            'role' => 'employee'
        ]);

        User::factory(3)->create();

        Product::factory(50)->create();
        Customer::factory(10)->create();
        Invoice::factory(20)->create();
        InvoiceItem::factory(20)->create();
        CustomerWallet::factory(20)->create();

        OutsourcedProduction::factory(20)->create();
    }
}
