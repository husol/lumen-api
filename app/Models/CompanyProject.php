<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class CompanyProject extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_projects';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * @var array
     */
    protected $guarded = ['id'];
    public $timestamps = false;
}
