<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Device extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'devices';
    protected $primaryKey = 'id_player';
    public $incrementing = false;
    /**
     * @var array
     */
    protected $guarded = [];
}
