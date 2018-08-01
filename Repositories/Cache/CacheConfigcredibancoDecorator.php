<?php

namespace Modules\IcommerceCredibanco\Repositories\Cache;

use Modules\IcommerceCredibanco\Repositories\ConfigcredibancoRepository;
use Modules\Core\Repositories\Cache\BaseCacheDecorator;

class CacheConfigcredibancoDecorator extends BaseCacheDecorator implements ConfigcredibancoRepository
{
    public function __construct(ConfigcredibancoRepository $configcredibanco)
    {
        parent::__construct();
        $this->entityName = 'icommercecredibanco.configcredibancos';
        $this->repository = $configcredibanco;
    }
}
