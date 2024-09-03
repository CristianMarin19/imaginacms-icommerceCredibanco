<?php

namespace Modules\Icommercecredibanco\Repositories\Cache;

use Modules\Icommercecredibanco\Repositories\IcommerceCredibancoRepository;
use Modules\Core\Repositories\Cache\BaseCacheDecorator;

class CacheIcommerceCredibancoDecorator extends BaseCacheDecorator implements IcommerceCredibancoRepository
{
  public function __construct(IcommerceCredibancoRepository $icommercecredibanco)
  {
    parent::__construct();
    $this->entityName = 'icommercecredibanco.icommercecredibancos';
    $this->repository = $icommercecredibanco;
  }

  public function calculate($parameters, $conf)
  {
    return $this->remember(function () use ($parameters, $conf) {
      return $this->repository->calculate($parameters, $conf);
    });
  }
}
