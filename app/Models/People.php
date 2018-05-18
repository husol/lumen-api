<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class People extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lit_people';
    protected $primaryKey = 'p_id';
    protected $maps = [
        'id' => 'p_id',
        'id_user' => 'u_id',
        'countview' => 'p_countview',
        'countlike' => 'p_countlike',
        'countdislike' => 'p_countdislike',
        'countcomment' => 'p_countcomment',
        'cover' => 'p_coverimage',
        'hinhthuc' => 'p_hinhthuc',
        'tinhchat' => 'p_tinhchat',
        'cate1' => 'p_cat1',
        'cate2' => 'p_cat2',
        'cate3' => 'p_cat3',
        'cate4' => 'p_cat4',
        'cate5' => 'p_cat5',
        'country' => 'p_country',
        'id_region' => 'p_region',
        'id_subregion' => 'p_subregion',
        'monthly_salary' => 'p_luongtheothang',
        'daily_salary' => 'p_luongtheongay',
        'birth_year' => 'p_namsinh',
        'gender' => 'p_gioitinh',
        'email' => 'p_email',
        'phone' => 'p_dienthoai',
        'experienced_year' => 'p_sonamkinhnghiem',
        'video_link' => 'p_videolink',
        'cert1' => 'p_bangcap1',
        'cert2' => 'p_bangcap2',
        'cert3' => 'p_bangcap3',
        'school1' => 'p_truong1',
        'school2' => 'p_truong2',
        'school3' => 'p_truong3',
        'is_featured' => 'p_isfeatured',
        'status' => 'p_status',
        'search_text' => 'p_searchtext'
    ];
    /**
     * @var array
     */
    protected $guarded = ['id'];

    const STATUS_ENABLE = 1;
    const STATUS_DISABLED = 2;
    const STATUS_PENDING = 3;
    const PER_PAGE = 50;
}
