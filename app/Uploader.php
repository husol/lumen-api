<?php
/**
 * Description of Uploader
 *
 * @author khoaht
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Uploader extends Model
{
    const ERROR_UPLOAD_OK = 0;
    const ERROR_UPLOAD_UNKNOWN = 1;
    const ERROR_FILESIZE = 2;
    const ERROR_FILETYPE = 4;

    public $endpoint = "";
    public $key = "";
    public $secret = "";
    public $bucket = "";
    public $pathfilename = "";
    public $sourcefile = "";
    public $sourcename = "";
    public $checkimage = false;
    public $maxfilesize = 0;

    public function __construct($new_path_filename, $source, $check_image = false, $max_file_size = 0)
    {
        date_default_timezone_set('Asia/Singapore');

        $this->endpoint = env('S3_ENDPOINT');
        $this->key = env('S3_KEY');
        $this->secret = env('S3_SECRET');
        $this->bucket = env('S3_BUCKETDATA');
        $this->pathfilename = $new_path_filename;
        if (is_array($source)) {
            $this->sourcefile = $source['file'];
            $this->sourcename = $source['name'];
        } else {
            $this->sourcefile = $source;
            $this->sourcename = basename($source);
        }

        $this->checkimage = $check_image;
        $this->maxfilesize = intval($max_file_size) > 0
            ? $this->returnByte($max_file_size)
            : $this->returnByte(ini_get('upload_max_filesize'));
    }

    public function upload()
    {
        $error = 0;

        //check file size
        if (filesize($this->sourcefile) > $this->maxfilesize) {
            $error = $error | self::ERROR_FILESIZE;
        }

        //check image file
        $ext = pathinfo($this->sourcename, PATHINFO_EXTENSION);
        if ($this->checkimage && !in_array(strtolower($ext), array('gif', 'jpg', 'jpeg', 'png'))) {
            $error = $error | self::ERROR_FILETYPE;
        }

        if ($error == 0) {
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
                // Upload data.
                $result = $s3->putObject(array(
                    'Bucket' => $this->bucket,
                    'Key' => $this->pathfilename,
                    'SourceFile' => $this->sourcefile,
                    'ACL' => 'public-read',
                    'ContentType' => mime_content_type($this->sourcefile)
                ));

                return array(
                    'error' => self::ERROR_UPLOAD_OK,
                    'path' => str_replace($this->endpoint.'/'.$this->bucket.'/', "", $result['ObjectURL'])
                );
            } catch (S3Exception $e) {
                return array(
                    'error' => self::ERROR_UPLOAD_UNKNOWN,
                    'info' => $e->getMessage()
                );
            }
        }

        return array(
            'error' => $error,
            'info' => 'Invalid file size or file type.'
        );
    }

    public function returnByte($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch ($last) {
            case 'g':
                $val = intval($val)*1024*1024*1024; //intension
            case 'm':
                $val = intval($val)*1024*1024; //intension
            case 'k':
                $val = intval($val)*1024;
        }

        return $val;
    }
}
