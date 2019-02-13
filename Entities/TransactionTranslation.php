<?php

namespace Modules\Icommercecredibanco\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [];
    protected $table = 'icommercecredibanco__transaction_translations';
}
