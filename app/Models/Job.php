<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Job extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'jobs';
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $guarded = ['id'];

    const STATUS_DISABLED = 0; //default
    const STATUS_ENABLE = 1;

    const PER_PAGE = 50;
}
