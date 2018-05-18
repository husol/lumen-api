<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class PeopleJob extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'people_jobs';
    protected $primaryKey = ['id_people', 'id_job'];
    public $incrementing = false;
    /**
     * @var array
     */
    protected $guarded = ['id_people', 'id_job'];

    const STATUS_REJECTED = 0;
    const STATUS_APPLIED = 1; //default
    const STATUS_SELECTED = 2;
}
