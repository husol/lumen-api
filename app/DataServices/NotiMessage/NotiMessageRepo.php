<?php

namespace App\DataServices\NotiMessage;

use App\DataServices\EloquentRepo;
use App\Models\NotiMessage;

class NotiMessageRepo extends EloquentRepo implements NotiMessageRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return NotiMessage::class;
    }

    public function getNotiMessageList($arrFilter = [])
    {
        $selectedFields = [
            'id_msg',
            'model_msg'
        ];

        $notiMessage = $this->model->orderBy('id_msg', 'ASC');

        //Additional Where
        if (isset($arrFilter['where'])) {
            foreach ($arrFilter['where'] as $field => $value) {
                $notiMessage->where($field, $value);
            }
        }
        //Additional Order
        if (isset($arrFilter['order'])) {
            foreach ($arrFilter['order'] as $field => $value) {
                $notiMessage->orderBy($field, $value);
            }
        }

        //Additional Limit
        if (isset($arrFilter['limit'])) {
            return $notiMessage->limit(intval($arrFilter['limit']))->get($selectedFields);
        }

        if (isset($arrFilter['no_paging']) && $arrFilter['no_paging']) {
            return $notiMessage->get($selectedFields);
        }

        return $notiMessage->paginate(NotiMessage::PER_PAGE, $selectedFields);
    }
}
