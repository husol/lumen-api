<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class PeopleMedia extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_people_media';
    protected $primaryKey = 'pm_id';
    protected $maps = [
        'id' => 'pm_id',
        'id_user' => 'u_id',
        'id_people' => 'p_id',
        'type' => 'pm_type',
        'caption' => 'pm_description',
        'file_path' => 'pm_filepath',
        'countview' => 'pm_countview'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const TYPE_IMAGE = 1;
    const TYPE_VIDEO = 2;
    const PER_PAGE = 50;
}
