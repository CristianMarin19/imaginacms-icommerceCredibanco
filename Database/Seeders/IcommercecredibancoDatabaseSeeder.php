<?php

namespace Modules\Icommercecredibanco\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Icommerce\Entities\PaymentMethod;
use Modules\Isite\Jobs\ProcessSeeds;

class IcommercecredibancoDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */

  public function run()
  {
    ProcessSeeds::dispatch([
      "baseClass" => "\Modules\Icommercecredibanco\Database\Seeders",
      "seeds" => ["IcommercecredibancoModuleTableSeeder", "IcommercecredibancoSeeder"]
    ]);
  }
}
