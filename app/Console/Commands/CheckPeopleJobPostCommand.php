<?php

namespace App\Console\Commands;

use App\DataServices\Job\JobRepo;
use App\DataServices\People\PeopleRepo;
use App\DataServices\PeopleJob\PeopleJobRepo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class CheckPeopleJobPostCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "PeopleJobPost:check";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check countview, countshare, countcomment from the post on Facebook.";

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
        $this->info('Start. Check countlike, countshare, countcomment on people job posts with status = 0');
        //Get all people_job_posts which have status = 0 (Not get the targets yet)
        $sql = "SELECT pjp.id, pjp.id_people, pjp.id_job, fb_id_post, target_like, up_fb_token AS fb_token
                FROM people_job_posts AS pjp
                    JOIN people_jobs AS pj ON pj.`id_people` = pjp.`id_people` AND pj.`id_job` = pjp.`id_job`
                    JOIN job_packages AS jp ON pj.`id_job` = jp.`id_job`
                    JOIN packages ON packages.id = jp.id_package
                    JOIN lit_people ON pj.`id_people` = lit_people.`p_id`
                    JOIN lit_ac_user_profile AS up ON lit_people.`u_id` = up.`u_id`
                WHERE pjp.`status` = 0";

        $peopleJobPosts = DB::select($sql);

        $fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v2.10',
        ]);
        $countFields = 'likes.limit(0).summary(true),shares,comments.limit(0).summary(true)';

        if (empty($peopleJobPosts)) {
            $this->info('End. No record to check');
            return false;
        }

        foreach ($peopleJobPosts as $peopleJobPost) {
            try {
                //Returns a `Facebook\FacebookResponse` object
                $response = $fb->get("/$peopleJobPost->fb_id_post?fields=$countFields", $peopleJobPost->fb_token);
            } catch (FacebookResponseException $e) {
                $this->error('Graph returned an error: ' . $e->getMessage());
                continue;
            } catch (FacebookSDKException $e) {
                $this->error('Facebook SDK returned an error: ' . $e->getMessage());
                continue;
            }

            $result = $response->getGraphUser();

            $likes = $result->getField('likes')->getMetaData();
            $shares = $result->getField('shares');
            $comments = $result->getField('comments')->getMetaData();

            //Update people_job_posts
            $dataUpdated = [
                'countlike' => $likes['summary']['total_count'],
                'countshare' => $shares['count'],
                'countcomment' => $comments['summary']['total_count'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            //Enable status to 1 if targets are satisfied, then increase candidate's income
            if ($likes['summary']['total_count'] >= $peopleJobPost->target_like) {
                $dataUpdated['status'] = 1;

                //Checkin for candidate who has finished job
                $repoPeopleJob = new PeopleJobRepo();
                $repoPeopleJob->updateWhere(
                    ['id_people' => $peopleJobPost->id_people, 'id_job' => $peopleJobPost->id_job],
                    ['checked_in_at' => date('Y-m-d H:i:s')]
                );

                $repoJob = new JobRepo();
                $myJob = $repoJob->find($peopleJobPost->id_job)->first();

                //Increase income
                $repoPeople = new PeopleRepo();
                $repoPeople->update($peopleJobPost->id_people, ['income' => DB::raw("income + $myJob->salary")]);
            }
            DB::table('people_job_posts')->where('id', $peopleJobPost->id)->update($dataUpdated);
        }

        $this->info('End. Checked countlike, countshare, countcomment on people job posts with status = 0');
        return true;
    }
}
