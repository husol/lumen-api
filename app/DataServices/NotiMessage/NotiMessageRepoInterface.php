<?php
namespace App\DataServices\NotiMessage;

interface NotiMessageRepoInterface
{
    public function getNotiMessageList($arrFilter = []);
}
