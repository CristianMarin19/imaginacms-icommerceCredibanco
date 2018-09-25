<?php

namespace Modules\IcommerceCredibanco\Repositories\Eloquent;

use Modules\IcommerceCredibanco\Repositories\TransactionRepository;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;

class EloquentTransactionRepository extends EloquentBaseRepository implements TransactionRepository
{

    public function findByOrder($id){
        return $this->model->where('order_id',"=",$id)->first();
    }

    public function findByOrderTrans($orderID,$transactionID){
        return $this->model->where([
            ['id',"=",$transactionID],['order_id',"=",$orderID]
            ])->first();
    }

}
