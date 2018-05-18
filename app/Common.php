<?php
/**
 * Description of Common
 *
 * @author khoaht
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\DataServices\People\PeopleRepo;
use App\DataServices\Company\CompanyRepo;

class Common extends Model
{
    public static $setting = array(
        'avatar' => array(
            'imageDirectory' => 'uploads/avatar/',
            'imageMaxSize' => 10485760, //unit in Byte, 10MB
            'imageMaxWidth' => 1000,
            'imageMaxHeight' => 1000,
            'imageMediumWidth' => 640,
            'imageMediumHeight' => 640,
            'imageThumbWidth' => 320,
            'imageThumbHeight' => 320
        ),
        'files' => array(
            'fileDirectory' => 'uploads/files/'
        ),
        'images' => array(
            'imageDirectory' => 'uploads/images/',
            'imageMaxSize' => 10485760, //unit in Byte, 10MB
            'imageMaxWidth' => 1000,
            'imageMaxHeight' => 1000,
            'imageMediumWidth' => 640,
            'imageMediumHeight' => 640,
            'imageThumbWidth' => 320,
            'imageThumbHeight' => 320
        ),
        'jobs' => array(
            'imageDirectory' => 'uploads/jobs/',
            'imageMaxSize' => 10485760, //unit in Byte, 10MB
            'imageMaxWidth' => 1000,
            'imageMaxHeight' => 1000,
            'imageMediumWidth' => 640,
            'imageMediumHeight' => 640,
            'imageThumbWidth' => 320,
            'imageThumbHeight' => 320
        ),
        'pages' => array(
            'imageDirectory' => 'uploads/page/',
            'imageMaxSize' => 10485760, //unit in Byte, 10MB
            'imageMaxWidth' => 1000,
            'imageMaxHeight' => 1000,
            'imageMediumWidth' => 640,
            'imageMediumHeight' => 640,
            'imageThumbWidth' => 320,
            'imageThumbHeight' => 320
        )
    );

    //Never change this string, if not, all account will invalid.
    //Need to change for each project
    public static $secretString = 'lkjhhfu484629fhdfgsgfjk3937dhksh';

    //if change this value, user password cookie will invalid.
    public static $salt = '94729fjhgadhwkdxwueodjd893729';

    public static $saltSeperator = ':';

    public static function hash($input, $full = true)
    {
        if ($full) {
            return md5($input . self::$salt) . self::$saltSeperator .
                base64_encode(md5(self::$secretString) . self::$salt);
        } else {
            return md5($input . self::$salt);
        }
    }

    public static function getQueryLog($all = false)
    {
        $queries = DB::getQueryLog();
        if ($all) {
            return $queries;
        }
        return end($queries);
    }

    public static function getRandomString($prefix = '', $suffix = '', $length = 13)
    {
        $seed = str_split('ABCDEFGHIJTUVWXYZabcdefghijklmnpqrstuvwxyz123456789');
        shuffle($seed);

        $rand = $prefix;
        foreach (array_rand($seed, $length) as $k) {
            $rand .= $seed[$k];
        }

        return $rand.$suffix;
    }

    public static function getLoggedUserInfo()
    {
        $selectedFields = [
            'u_groupid AS group_id',
            'u_fullname AS fullname',
            'u_avatar AS avatar'
        ];

        $loggedUser = Auth::user();
        if (is_null($loggedUser)) {
            return false;
        }

        $loggedUser = $loggedUser->toArray();
        $user = DB::table('lit_ac_user')
            ->where('u_id', $loggedUser['u_id'])
            ->first($selectedFields);

        foreach ($loggedUser as $k => $info) {
            switch ($k) {
                case 'u_id':
                    $k = 'id';
                    break;
                case 'up_phone':
                    $k = 'phone';
                    break;
                case 'up_email':
                    $k = 'email';
                    break;
                case 'up_lastuseragent':
                    $k = 'last_user_agent';
                    break;
                case 'up_fb_token':
                    $k = 'fb_token';
                    break;
            }
            $user->$k = $info;
        }

        if (!empty($user->avatar)) {
            $user->avatar = self::getImgUrl() . $user->avatar;
        }

        //Get id people if any
        $repoPeople = new PeopleRepo();
        $myPeople = $repoPeople->getByUserId($user->id);

        if (!empty($myPeople)) {
            $user->id_people = $myPeople->id;
        }

        //Get id company if any
        $repoCompany = new CompanyRepo();
        $myCompany = $repoCompany->getByUserId($user->id);

        if (!empty($myCompany)) {
            $user->id_company = $myCompany->id;
        }

        return $user;
    }

    public static function getImgUrl()
    {
        $s3Enpoint = env('S3_ENDPOINT');
        $s3BucketData = env('S3_BUCKETDATA');

        return $s3Enpoint.'/'.$s3BucketData.'/';
    }

    //$sourcefile = array('file' => "path/file", 'name' => "origin_name")
    public static function uploadFile($sourcefile, $prefix = '', $suffix = '')
    {
        //Upload file
        $curDateDir = getCurrentDateDirName();  //path format: ../2009/September/
        $extPart = substr(strrchr($sourcefile['name'], '.'), 1);

        $namePart = time();
        if ($prefix != '') {
            $namePart = $prefix."_".$namePart;
        }
        if ($suffix != '') {
            $namePart = $namePart."_".$suffix;
        }
        $name = $namePart . '.' . $extPart;
        $file = self::$setting['files']['fileDirectory'] . $curDateDir . $name;

        $uploader = new Uploader($file, $sourcefile);
        $result = $uploader->upload();

        return $result;
    }

    //$sourcefile = array('file' => "path/file", 'name' => "origin_name")
    public static function uploadImage($sourcefile, $type = 'images', $prefix = '', $suffix = '')
    {
        //Upload origin image
        $curDateDir = getCurrentDateDirName();
        $extPart = substr(strrchr($sourcefile['name'], '.'), 1);
        $namePart = time();
        if ($prefix != '') {
            $namePart = $prefix."_".$namePart;
        }
        if ($suffix != '') {
            $namePart = $namePart."_".$suffix;
        }
        $name = $namePart . '.' . $extPart;
        $image = self::$setting[$type]['imageDirectory'] . $curDateDir . $name;

        $uploader = new Uploader($image, $sourcefile, true, self::$setting[$type]['imageMaxSize']);
        $result = $uploader->upload();

        if ($result['error'] == 0) {
            //Create thumb image
            $nameThumbPart = substr($name, 0, strrpos($name, '.'));
            $nameThumb = $nameThumbPart . '-small.' . $extPart;
            $thumbImage = '/tmp/' . $nameThumb;

            File::resizeImageThumbnail(
                $sourcefile['file'],
                $thumbImage,
                self::$setting[$type]['imageThumbWidth'],
                self::$setting[$type]['imageThumbHeight']
            );
            $imageThumb = self::$setting[$type]['imageDirectory'] . $curDateDir . $nameThumb;
            $uploader = new Uploader($imageThumb, $thumbImage);
            $uploader->upload();

            //Create medium image
            $nameMediumPart = substr($name, 0, strrpos($name, '.'));
            $nameMedium = $nameMediumPart . '-medium.' . $extPart;
            $mediumImage = '/tmp/' . $nameMedium;

            File::resizeImageThumbnail(
                $sourcefile['file'],
                $mediumImage,
                self::$setting[$type]['imageMediumWidth'],
                self::$setting[$type]['imageMediumHeight']
            );
            $imageMedium = self::$setting[$type]['imageDirectory'] . $curDateDir . $nameMedium;
            $uploader = new Uploader($imageMedium, $mediumImage);
            $uploader->upload();
        }

        return $result;
    }

    public static function resizeImageThumbnail($source, $destination, $thumbWidth, $thumbHeight)
    {
        $size = getimagesize($source);
        $imageWidth  = $newWidth  = $size[0];
        $imageHeight = $newHeight = $size[1];
        $extension = image_type_to_extension($size[2]);

        if ($imageWidth > $thumbWidth || $imageHeight > $thumbHeight) {
            // Calculate the ratio
            $xscale = ($imageWidth/$thumbWidth);
            $yscale = ($imageHeight/$thumbHeight);
            $newWidth  = ($yscale > $xscale) ? round($imageWidth * (1/$yscale)) : round($imageWidth * (1/$xscale));
            $newHeight = ($yscale > $xscale) ? round($imageHeight * (1/$yscale)) : round($imageHeight * (1/$xscale));
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        switch ($extension) {
            case '.jpeg':
            case '.jpg':
                $imageCreateFrom = 'imagecreatefromjpeg';
                $store = 'imagejpeg';
                break;

            case '.png':
                $imageCreateFrom = 'imagecreatefrompng';
                $store = 'imagepng';
                //Removing the black from the placeholder
                $background = imagecolorallocate($newImage, 0, 0, 0);
                imagecolortransparent($newImage, $background);
                break;

            case '.gif':
                $imageCreateFrom = 'imagecreatefromgif';
                $store = 'imagegif';
                break;

            default:
                return false;
        }

        $container = $imageCreateFrom($source);

        imagecopyresampled($newImage, $container, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);

        // Fix Orientation
        $exif = @exif_read_data($source);
        $orientation = $exif['Orientation'];

        switch ($orientation) {
            case 3:
                $newImage = imagerotate($newImage, 180, 0);
                break;
            case 6:
                $newImage = imagerotate($newImage, -90, 0);
                break;
            case 8:
                $newImage = imagerotate($newImage, 90, 0);
                break;
        }

        return $store($newImage, $destination);
    }

    public static function isImageFilename($filename)
    {
        $type = array('jpg', 'jpeg', 'png', 'gif', 'bmp');

        $extension = explode(".", $filename);
        $ext = end($extension);

        if ((in_array(strtolower($ext), $type))) {
            return true;
        }

        return false;
    }
}
