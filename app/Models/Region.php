<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Region extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_region';
    protected $primaryKey = 'r_id';
    protected $maps = [
        'id' => 'r_id',
        'parent_id' => 'r_parentid',
        'name' => 'r_name',
        'slug' => 'r_slug',
        'country' => 'r_country',
        'level' => 'r_level',
        'latitude' => 'r_lat',
        'longitude' => 'r_lng'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];
    public $timestamps = false;

    const PER_PAGE = 170;
}
