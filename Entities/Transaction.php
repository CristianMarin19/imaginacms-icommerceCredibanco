<?php

namespace Modules\Icommercecredibanco\Entities;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use Translatable;

    protected $table = 'icommercecredibanco__transactions';
    public $translatedAttributes = [];
    protected $fillable = [];
}
