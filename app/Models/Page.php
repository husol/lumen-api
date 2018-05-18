<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Page extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_page';
    protected $primaryKey = 'pg_id';
    protected $maps = [
        'id' => 'pg_id',
        'id_page_category' => 'pc_id',
        'countview' => 'pg_countview',
        'image' => 'pg_image',
        'type' => 'pg_type',
        'template_identifier' => 'pg_templateidentifier',
        'tag' => 'pg_tag',
        'status' => 'pg_status',
        'is_featured' => 'pg_isfeatured',
        'date_created' => 'pg_datecreated'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];
    const TYPE_PAGE = 1;
    const TYPE_NEWS = 2;
    const TYPE_FAQ = 3;
    const PER_PAGE = 50;
}
