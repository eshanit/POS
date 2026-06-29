<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Bulk Distributors', 'contact_person' => 'John Doe', 'phone' => '0771234567', 'email' => 'john@bulkdist.co.zw'],
            ['name' => 'Wholesale Mart', 'contact_person' => 'Jane Smith', 'phone' => '0772345678', 'email' => 'jane@wholesalemart.co.zw'],
            ['name' => 'Prime Goods', 'contact_person' => 'Peter Jones', 'phone' => '0773456789', 'email' => 'peter@primegoods.co.zw'],
            ['name' => 'City Traders', 'contact_person' => 'Sarah Lee', 'phone' => '0774567890', 'email' => 'sarah@citytraders.co.zw'],
            ['name' => 'Global Imports', 'contact_person' => 'Mike Chen', 'phone' => '0775678901', 'email' => 'mike@globalimports.co.zw'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
