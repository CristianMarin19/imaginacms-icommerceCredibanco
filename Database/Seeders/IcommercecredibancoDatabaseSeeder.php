<?php

namespace Modules\Icommercecredibanco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Icommerce\Entities\PaymentMethod;
use Modules\Isite\Jobs\ProcessSeeds;

class IcommercecredibancoDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {

    Model::unguard();
    ProcessSeeds::dispatch([
      "baseClass" => "\Modules\Icommercecredibanco\Database\Seeders",
      "seeds" => ["IcommercecredibancoModuleTableSeeder"]
    ]);

    $name = config('asgard.icommercecredibanco.config.paymentName');
    $result = PaymentMethod::where('name', $name)->first();

    if (!$result) {
      $options['init'] = "Modules\Icommercecredibanco\Http\Controllers\Api\IcommerceCredibancoApiController";

      $options['mainimage'] = null;
      $options['user'] = "";
      $options['password'] = "";
      $options['merchantId'] = "";
      $options['mode'] = "sandbox";
      $options['minimunAmount'] = 0;
      $options['showInCurrencies'] = ["COP"];

      $titleTrans = 'icommercecredibanco::icommercecredibancos.single';
      $descriptionTrans = 'icommercecredibanco::icommercecredibancos.description';

      foreach (['en', 'es'] as $locale) {

        if ($locale == 'en') {
          $params = array(
            'title' => trans($titleTrans),
            'description' => trans($descriptionTrans),
            'name' => $name,
            'status' => 1,
            'options' => $options
          );

          $paymentMethod = PaymentMethod::create($params);

        } else {

          $title = trans($titleTrans, [], $locale);
          $description = trans($descriptionTrans, [], $locale);

          $paymentMethod->translateOrNew($locale)->title = $title;
          $paymentMethod->translateOrNew($locale)->description = $description;

          $paymentMethod->save();
        }

      }// Foreach

    } else {
      $this->command->alert("This method has already been installed !!");
    }

  }
}
