<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class PeopleFeedback extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_people_feedback';
    protected $primaryKey = 'pf_id';
    public $incrementing = true;
    protected $maps = [
        'id' => 'pf_id',
        'id_user' => 'u_id',
        'id_people' => 'p_id',
        'rating' => 'pf_rating',
        'content' => 'pf_content',
        'status' => 'pf_status'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1; //default
    const PER_PAGE = 50;
}
