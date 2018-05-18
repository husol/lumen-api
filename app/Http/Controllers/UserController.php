<?php

namespace App\Http\Controllers;

use App\DataServices\PeopleExperience\PeopleExperienceRepo;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\DataServices\User\UserRepoInterface;
use App\DataServices\People\PeopleRepo;
use App\DataServices\Company\CompanyRepo;
use App\DataServices\File\FileRepo;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use App\Models\User;
use App\Models\PeopleMedia;
use App\Error;
use App\Common;

class UserController extends Controller
{
    protected $repoUser;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepoInterface $repoUser)
    {
        $this->repoUser = $repoUser;
    }

    public function profile(Request $request)
    {
        $loggedUserInfo = Common::getLoggedUserInfo();

        $result['id'] = $loggedUserInfo->id;
        $result['full_name'] = $loggedUserInfo->fullname;
        $result['avatar'] = $loggedUserInfo->avatar;

        if (isset($loggedUserInfo->id_people) && $loggedUserInfo->id_people > 0) {
            $repoPeople = new PeopleRepo();
            $myPeople = $repoPeople->getByUserId($loggedUserInfo->id);

            $result['people']['avg_rating'] = floatval($myPeople->avg_rating);
            $result['people']['countview'] = intval($myPeople->countview);
            $result['people']['countlike'] = intval($myPeople->countlike);
            $result['people']['income'] = floatval($myPeople->income);
            $result['people']['done_job_total'] = $repoPeople->getDoneJobTotal($loggedUserInfo->id_people);
        }
        if (isset($loggedUserInfo->id_company) && $loggedUserInfo->id_company > 0) {
            $repoCompany = new CompanyRepo();
            $myCompany = $repoCompany->getByUserId($loggedUserInfo->id);

            $result['company']['avg_rating'] = floatval($myCompany->avg_rating);
            $result['company']['countview'] = intval($myCompany->countview);
            $result['company']['posted_job_total'] = $repoCompany->getPostedJobTotal($loggedUserInfo->id_company);
        }

        return responseJson($result);
    }

    public function connectFacebook(Request $request)
    {
        $this->validate($request, [
            'access_token' => 'required|string'
        ]);

        $fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v2.10',
        ]);

        $accessToken = $request->input('access_token');

        try {
            //Returns a `Facebook\FacebookResponse` object
            $fields = "id,name,gender,birthday,picture.width(640).height(640),work,albums,posts{id}";
            $response = $fb->get("/me?fields=$fields", $accessToken);
        } catch (FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $fbUser = $response->getGraphUser();

        //Update logged in user if necessary
        $loggedUser = Common::getLoggedUserInfo();
        if (empty($loggedUser->fullname) || $loggedUser->fullname == $loggedUser->phone) {
            $loggedUser->fullname = $fbUser->getName();
            DB::table('lit_ac_user')
                ->where('u_id', $loggedUser->id)
                ->update(['u_fullname' => $loggedUser->fullname]);
        }

        if (empty($loggedUser->avatar)) {
            $fbAvatar = $fbUser->getPicture();
            $coverContent = file_get_contents($fbAvatar->getUrl());
            $pathAvatar = "/tmp/fb_avatar_$loggedUser->id";
            file_put_contents($pathAvatar, $coverContent);
            $sourceFile = ['file'=> $pathAvatar, 'name' => "fb_avatar_$loggedUser->id.jpg"];
            $prefix = codau2khongdau($loggedUser->fullname, true);

            $result = Common::uploadImage($sourceFile, 'avatar', $prefix);

            if ($result['error'] == 0) {
                DB::table('lit_ac_user')
                    ->where('u_id', $loggedUser->id)
                    ->update(['u_avatar' => $result['path']]);
                $loggedUser->avatar = Common::getImgUrl() . $result['path'];
            }
        }

        //Update fb_id, fb_token
        $this->repoUser->update($loggedUser->id, ['fb_id' => $fbUser->getId(), 'fb_token' => $accessToken]);

        //Update birth_year, gender
        $gender = $fbUser->getGender();
        $birthday = $fbUser->getBirthday();
        if (!empty($birthday)) {
            $birthYear = date('Y', $birthday->getTimestamp());
        }

        //Update countlike
        $countFields = 'likes.limit(0).summary(true)';
        $posts = $fbUser->getField('posts');
        $posts = $posts->asArray();
        $totalLikes = 0;
        $count = 0;
        $flag = true;
        foreach ($posts as $post) {
            if ($count == 5) {
                break;
            }
            try {
                //Returns a `Facebook\FacebookResponse` object
                $response = $fb->get("/{$post['id']}?fields=$countFields", $accessToken);
            } catch (FacebookResponseException $e) {
                $this->error('Graph returned an error: ' . $e->getMessage());
                $flag = false;
                break;
            } catch (FacebookSDKException $e) {
                $this->error('Facebook SDK returned an error: ' . $e->getMessage());
                break;
            }
            $result = $response->getGraphUser();

            $likes = $result->getField('likes')->getMetaData();
            $totalLikes += $likes['summary']['total_count'];
            $count++;
        }
        if ($flag) {
            $countLike = ceil($totalLikes / $count);
        }

        $repoPeople = new PeopleRepo();
        $people = $repoPeople->getByUserId($loggedUser->id);

        if (empty($people)) {
            $dataCreated = [
                'id_user' => $loggedUser->id,
                'gender' => ($gender == 'male' ? 1 : 2),
                'birth_year' => isset($birthYear) ? $birthYear : ''
            ];
            if ($flag) {
                $dataCreated['countlike'] = $countLike;
            }
            $people = $repoPeople->create($dataCreated);
        } else {
            $dataUpdated = [];
            if ($flag) {
                $dataUpdated['countlike'] = $countLike;
            }

            if (empty($people->gender)) {
                $dataUpdated['gender'] = ($gender == 'male' ? 1 : 2);
            }
            if (empty($people->birth_year) && isset($birthYear)) {
                $dataUpdated['birth_year'] = $birthYear;
            }
            if (!empty($dataUpdated)) {
                $repoPeople->update($people->id, $dataUpdated);
            }
        }

        //Update people experiences from facebook if there's no experience record
        $repoPeopleExp = new PeopleExperienceRepo();
        $peopleExps = $repoPeopleExp->getByPeopleId($people->id);

        if ($peopleExps->isEmpty()) {
            $works = $fbUser->getField('work');
            if (!empty($works)) {
                foreach ($works as $work) {
                    $experience = [
                        'id_people' => $people->id
                    ];
                    $position = $work->getField('position');
                    $employer = $work->getField('employer');
                    $experience['title'] = $position->getField('name')." tại ".$employer->getField('name');
                    $location = $work->getField('location');

                    $experience['working_place'] = !empty($location) ? $location->getField('name') : '';
                    if ($work->getField('start_date') != '0000-00') {
                        $experience['start_date'] = $work->getField('start_date');
                    }
                    if ($work->getField('end_date') != '0000-00') {
                        $experience['end_date'] = $work->getField('end_date');
                    }

                    $repoPeopleExp->create($experience);
                }
            }
        }

        //Update people media from facebook if there's no media record
        $repoFile = new FileRepo();
        $medias = $repoFile->getByPeopleId($people->id);
        $albums = $fbUser->getField('albums');

        if ($medias->isEmpty() && !empty($albums)) {
            $albums = $albums->asArray();
            $albumId = '';
            foreach ($albums as $album) {
                if ($album['name'] != 'Profile Pictures') {
                    continue;
                }
                $albumId = $album['id'];
            }

            if (!empty($albumId)) {
                $albumReq = $fb->get("/$albumId/photos", $accessToken);
                $photos = $albumReq->getGraphEdge()->asArray();

                $count = 0;
                foreach ($photos as $photo) {
                    if ($count == 5) {
                        break;
                    }
                    $photoReq = $fb->get('/'.$photo['id'].'?fields=images', $accessToken);
                    $media = $photoReq->getGraphNode()->asArray();
                    //Get the largest image
                    $maxWidth = $media['images'][0]['width'];
                    $source = $media['images'][0]['source'];
                    foreach ($media['images'] as $image) {
                        if ($maxWidth < $image['width']) {
                            $maxWidth = $image['width'];
                            $source = $image['source'];
                        }
                    }
                    $mediaContent = file_get_contents($source);
                    $pathMedia = "/tmp/fb_photo_{$photo['id']}";
                    file_put_contents($pathMedia, $mediaContent);
                    $sourceFile = ['file'=> $pathMedia, 'name' => "fb_avatar_$loggedUser->id.jpg"];
                    $prefix = codau2khongdau($loggedUser->fullname, true);
                    $result = Common::uploadImage($sourceFile, 'images', $prefix);

                    if ($result['error'] == 0) {
                        $count++;
                        $repoFile->create([
                            'id_user' => $loggedUser->id,
                            'id_people' => $people->id,
                            'type' => PeopleMedia::TYPE_IMAGE,
                            'file_path' => $result['path']
                        ]);
                    }
                }
            }
        }

        $loggedUser->id_people = $people->id;

        return responseJson($loggedUser);
    }

    public function login(Request $request)
    {
        $err = new Error();

        $loginAK = false;
        if ($request->has('code')) {
            $this->validate($request, [
                'phone' => 'required|digits_between:10,11',
                'code' => 'required'
            ]);

            //username must be phone
            $username = $request->input('phone');
            $code = $request->input('code');

            $ch = curl_init();
            //Set url elements
            $fbAppId = env('FACEBOOK_APP_ID');
            $akSecret = env('FACEBOOK_AK_SECRET');
            $token = 'AA|'.$fbAppId.'|'.$akSecret;
            //Get access token
            $url = "https://graph.accountkit.com/v1.2/access_token?grant_type=authorization_code&code=".
                $code."&access_token=".$token;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            $info = json_decode($result);

            if (isset($info->error) && !empty($info->error)) {
                $err->setError('invalid_code', $info->error->message);
                return responseJson($err->getErrors(), 501);
            }

            $accessToken = $info->access_token;
            $appSecretProof = hash_hmac('sha256', $accessToken, $akSecret);

            //Get account information
            $url = "https://graph.accountkit.com/v1.2/me/?access_token={$accessToken}&appsecret_proof=".
                    $appSecretProof;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            curl_close($ch);
            $info = json_decode($result);

            //Ok, phone is validated by Account Kit, check user with phone now
            if (!isset($info->id) || $info->id <= 0) {
                $err->setError('error_validate_accountkit', "Login with Account Kit not successfully");
                return responseJson($err->getErrors(), 401);
            }
            $loginAK = true;
        } else {
            $this->validate($request, [
                'username' => 'required|string',
                'password' => 'required'
            ]);
            //username is email or phone
            $username = $request->input('username');
        }

        $user = $this->repoUser->getByEmail($username);
        if (is_null($user)) {
            $user = $this->repoUser->getByPhone($username);
        }

        if (is_null($user)) {
            if (!$loginAK) {//Login without Account Kit and not found user
                $err->setError('user_not_found', 'User is not found');
                return responseJson($err->getErrors(), 404);
            }
            $password = str_random(6);
            $password = Common::hash($password);
            //Create new user
            $dataInserted = [
                'u_fullname' => $username,//username must be phone here
                'u_groupid' => User::GROUPID_MEMBER
            ];
            $id = DB::table('lit_ac_user')->insertGetId($dataInserted);
            $dataInserted = [
                'u_id' => $id,
                'phone' => $username,//username must be phone here
                'password' => $password
            ];
            $this->repoUser->create($dataInserted);

            $user = $this->repoUser->getByPhone($username);
        }

        if ($loginAK || Common::hash($request->input('password')) == $user->password) {
            ///////Generate Token for api user//////
            $audience = 'canavi';
            $customClaims = [
                'iss' => $audience,
                'aud' => $audience,
                'iat' => time(),
                'exp' => time() + 14 * 864000,
                'jti' => md5($user->id . '-' . $audience . '-' . time())
            ];

            try {
                if (!$token = JWTAuth::fromUser($user, $customClaims)) {
                    $err->setError('user_not_found', 'User is not found');
                    return responseJson($err->getErrors(), 404);
                }
            } catch (TokenExpiredException $e) {
                $err->setError('token_expired', 'Token is expired');
                return responseJson($err->getErrors(), $e->getStatusCode());
            } catch (TokenInvalidException $e) {
                $err->setError('token_invalid', 'Token is invalid');
                return responseJson($err->getErrors(), $e->getStatusCode());
            } catch (JWTException $e) {
                $err->setError('token_absent', $e->getMessage());
                return responseJson($err->getErrors(), $e->getStatusCode());
            }

            //Update on login
            $dataUpdated = [
                'last_user_agent' => $request->header('User-Agent'),
                'last_login_at' => date('Y-m-d H:i:s', time())
            ];
            $this->repoUser->update($user->id, $dataUpdated);

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

            $response = [
                'jwt' => compact('token'),
                'user' => $user
            ];

            return responseJson($response);
        } else {
            $err->setError('invalid_user_password', 'Invalid Username or Password');
            return responseJson($err->getErrors(), 401);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'fullname' => 'string|min:3'
        ]);
        $fullname = $request->input('fullname');

        if (!empty($fullname)) {
            $loggedUser = Common::getLoggedUserInfo();
            DB::table('lit_ac_user')->where('u_id', $loggedUser->id)->update(['u_fullname' => $fullname]);
        }

        $loggedUser = Common::getLoggedUserInfo();
        return responseJson($loggedUser);
    }
}
