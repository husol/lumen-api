<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Order extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $guarded = ['id'];

    const TYPE_ONE_TIME = 1;
    const STATUS_PENDING = 1; //default
    const STATUS_SUCCESS = 2;

    const PER_PAGE = 50;
}
