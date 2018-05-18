<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class PackageType extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'package_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * @var array
     */
    protected $guarded = ['id'];
    public $timestamps = false;

    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;
    const STATUS_INVISABLE = 2;
    const PER_PAGE = 50;
}
