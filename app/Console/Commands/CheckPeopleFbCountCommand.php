<?php

namespace App\Console\Commands;

use App\DataServices\People\PeopleRepo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class CheckPeopleFbCountCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "People:checkFbCount";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check countlike = total of 5 post's countlike / 5 from Facebook.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Start. Check countlike = total of 5 post's countlike / 5 from Facebook");
        //Get 500 people_to check countlike from facebook
        $sql = "SELECT p_id AS id, p.u_id AS id_user, up.up_fb_token AS fb_token
                FROM lit_people p JOIN lit_ac_user_profile up ON p.u_id = up.`u_id`
                WHERE p.`p_status` > -1 AND up.up_fb_token <> ''
                ORDER BY last_check_fb_count
                LIMIT 500";

        $people = DB::select($sql);

        $fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v2.10',
        ]);

        if (empty($people)) {
            $this->info('End. No record to check');
            return false;
        }

        $repoPeople = new PeopleRepo();
        foreach ($people as $candidate) {
            try {
                //Returns a `Facebook\FacebookResponse` object
                $fields = "posts{id}";
                $response = $fb->get("/me?fields=$fields", $candidate->fb_token);
            } catch (FacebookResponseException $e) {
                $this->error("Error: id_people = {$candidate->id}. Graph returned an error: ".
                    $e->getMessage());
                continue;
            } catch (FacebookSDKException $e) {
                $this->error("Error: id_people = {$candidate->id}. Facebook SDK returned an error: ".
                    $e->getMessage());
                continue;
            }

            $fbUser = $response->getGraphUser();
            //Update countlike to people
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
                    $response = $fb->get("/{$post['id']}?fields=$countFields", $candidate->fb_token);
                } catch (FacebookResponseException $e) {
                    $this->error('Graph returned an error: ' . $e->getMessage());
                    $flag = false;
                    break;
                } catch (FacebookSDKException $e) {
                    $this->error('Facebook SDK returned an error: ' . $e->getMessage());
                    $flag = false;
                    break;
                }
                $result = $response->getGraphUser();

                $likes = $result->getField('likes')->getMetaData();
                $totalLikes += $likes['summary']['total_count'];
                $count++;
            }
            if ($flag) {
                $countLike = ceil($totalLikes / $count);

                $dataUpdated = [
                    'countlike' => $countLike,
                    'last_check_fb_count' => date('Y-m-d H:i:s')
                ];
                $repoPeople->update($candidate->id, $dataUpdated);
            }
        }

        $this->info("End. Checked countlike = total of 5 post's countlike / 5 from Facebook");
        return true;
    }
}
