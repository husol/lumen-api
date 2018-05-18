<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class CompanyFeedback extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_feedbacks';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1; //default
    const PER_PAGE = 50;
}
