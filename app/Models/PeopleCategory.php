<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class PeopleCategory extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_people_category';
    protected $primaryKey = 'pc_id';
    protected $maps = [
        'id' => 'pc_id',
        'id_user' => 'u_id',
        'parent_id' => 'pc_parentid',
        'name' => 'pc_name',
        'slug' => 'pc_slug'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];
    public $timestamps = false;

    const PER_PAGE = 170;
}
