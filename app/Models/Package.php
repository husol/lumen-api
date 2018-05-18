<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Package extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packages';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const PER_PAGE = 50;
}
