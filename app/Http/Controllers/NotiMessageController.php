<?php

namespace App\Http\Controllers;

use App\DataServices\NotiMessage\NotiMessageRepoInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class NotiMessageController extends Controller
{
    protected $repoNotiMsg;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(NotiMessageRepoInterface $repoNotiMsg)
    {
        $this->repoNotiMsg = $repoNotiMsg;
    }

    public function getList(Request $request)
    {
        $arrFileter = [
            'no_paging' => 1
        ];
        $notiMessages = $this->repoNotiMsg->getNotiMessageList($arrFileter);

        if ($notiMessages->isEmpty()) {
            return responseJson([]);
        }

        return responseJson($notiMessages);
    }
}
