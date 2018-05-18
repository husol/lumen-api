<?php

namespace App\Jobs;

class NotificationJob extends Job
{
    protected $id_players;
    protected $data;
    protected $message;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id_players = [], $data = [], $message = "")
    {
        $this->id_players = $id_players;
        $this->data = $data;
        $this->message = $message;
    }

    public function sendMessage()
    {
        $content = array(
            "en" => $this->message,
            "vi" => $this->message
        );

        $fields = array(
            'app_id' => env("ONESIGNAL_APP_ID"),
            'include_player_ids' => $this->id_players,
            'data' => $this->data,
            'contents' => $content
        );

        $fields = json_encode($fields);
        $onesignal_apikey = env("ONESIGNAL_API_KEY");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            "Authorization: Basic $onesignal_apikey"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = $this->sendMessage();
    }
}
