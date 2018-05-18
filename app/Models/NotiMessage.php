<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class NotiMessage extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'noti_messages';
    protected $primaryKey = 'id_msg';
    public $incrementing = false;
    /**
     * @var array
     */
    protected $guarded = [];
    public $timestamps = false;

    const PER_PAGE = 50;
}
