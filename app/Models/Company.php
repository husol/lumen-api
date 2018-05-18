<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Company extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_company';
    protected $primaryKey = 'c_id';
    protected $maps = [
        'id' => 'c_id',
        'id_user' => 'u_id',
        'name' => 'c_name',
        'logo' => 'c_logo',
        'website' => 'c_website',
        'email' => 'c_email',
        'phone' => 'c_phone',
        'address' => 'c_address',
        'countview' => 'c_countview',
        'description' => 'c_description',
        'status' => 'c_status'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const STATUS_ENABLE = 1;
    const PER_PAGE = 50;
}
