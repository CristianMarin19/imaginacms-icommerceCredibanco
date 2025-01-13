<?php

namespace Modules\Icommercepayzen\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class IcommercecredibancoSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {

//    Model::unguard();

    $name = config('asgard.icommercecredibanco.config.paymentName');
    $paymentMethod = PaymentMethod::where('name', $name)->first();
    $PaymentMethodRepository = app('Modules\Icommerce\Repositories\PaymentMethodRepository');

    if (!$paymentMethod) {
      $options['init'] = "Modules\Icommercecredibanco\Http\Controllers\Api\IcommerceCredibancoApiController";

      $options['mainimage'] = null;
      $options['user'] = '';
      $options['password'] = '';
      $options['merchantId'] = '';
      $options['mode'] = 'sandbox';
      $options['minimunAmount'] = 0;
      $options['showInCurrencies'] = ['COP'];

      $titleTrans = 'icommercecredibanco::icommercecredibancos.single';
      $descriptionTrans = 'icommercecredibanco::icommercecredibancos.description';

      foreach (['en', 'es'] as $locale) {
        if ($locale == 'en') {
          $params = [
            'title' => trans($titleTrans),
            'description' => trans($descriptionTrans),
            'name' => $name,
            'status' => 1,
            'options' => $options,
          ];

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
      if ($paymentMethod->description != trans('icommercecredibanco::icommercecredibancos.iaDescription', [], locale())) {
        $data = array(
          'es' => ['description' => trans('icommercecredibanco::icommercecredibancos.iaDescription', [], 'es')],
          'en' => ['description' => trans('icommercecredibanco::icommercecredibancos.iaDescription', [], 'en')]
        );
        $paymentMethod = $PaymentMethodRepository->update($paymentMethod, $data);
        //Instance file service
        $fileService = app("Modules\Media\Services\FileService");
        //Instance the file path
        $filePath = 'Modules/Icommercecredibanco/Resources/img/credibanco_default.png';
        if (Storage::disk('local')->exists($filePath)) {
          // Obtener el contenido del archivo
          $fileContents = Storage::disk('local')->get($filePath);
          // Convertir el archivo a base64
          $base64File = base64_encode($fileContents);
          //Get base64 file
          $uploadedFile = getUploadedFileFromBase64($base64File);
          //Create file
          $file = $fileService->store($uploadedFile, 0, 'publicmedia');
          //set file if
          $fileId = $file->id;
          //Sync file id
          $paymentMethod->files()->attach($fileId, ['zone' => 'mainimage']);
        }
      }
    }
  }
}