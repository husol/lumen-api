<?php
/**
 * Description of File
 *
 * @author khoaht
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    public $endpoint = "";
    public $key = "";
    public $secret = "";
    public $bucket = "";
    public $pathfilename = "";

    public function __construct($path_filename)
    {
        date_default_timezone_set('Asia/Singapore');

        $this->endpoint = env('S3_ENDPOINT');
        $this->key = env('S3_KEY');
        $this->secret = env('S3_SECRET');
        $this->bucket = env('S3_BUCKETDATA');
        $this->pathfilename = $path_filename;
    }

    public function delete()
    {
        $s3 = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'ap-southeast-1',
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
        ]);

        try {
            // Delete data.
            $result = $s3->deleteObject(array(
                'Bucket' => $this->bucket,
                'Key' => $this->pathfilename
            ));

            return array(
                'error' => 0,
                'info' => "Delete success."
            );
        } catch (S3Exception $e) {
            return array(
                'error' => 1,
                'info' => $e->getMessage()
            );
        }
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

        if (!empty($exif['Orientation'])) {
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
        }

        return $store($newImage, $destination);
    }
}
