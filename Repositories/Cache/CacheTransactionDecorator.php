<?php

namespace Modules\IcommerceCredibanco\Repositories\Cache;

use Modules\IcommerceCredibanco\Repositories\TransactionRepository;
use Modules\Core\Repositories\Cache\BaseCacheDecorator;

class CacheTransactionDecorator extends BaseCacheDecorator implements TransactionRepository
{
    public function __construct(TransactionRepository $transaction)
    {
        parent::__construct();
        $this->entityName = 'icommercecredibanco.transactions';
        $this->repository = $transaction;
    }
}
