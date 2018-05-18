<?php

use Illuminate\Support\Facades\DB;

class PageTest extends TestCase
{
    public function testListPageFeatured()
    {
        $arrFilter = [
            'is_featured' => 1,
            'limit' => 30
        ];

        $result = $this->json('GET', '/v2/pages', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $flag = true;
            $objResult = json_decode($result->response->getContent());

            foreach ($objResult->result as $objID){
                $arrListId[] = $objID->id;
            }

            $queryRow = DB::table('lit_page')->where('pg_isfeatured', 1)->whereIn('pg_id', $arrListId)->get();
            mlog($queryRow);die;
            foreach ($objResult->result as $objPage) {

                if ($queryRow->pg_isfeatured != 1) {
                    $flag = false;
                    break;
                }
            }
            $result->assertTrue($flag);
        }
    }

    public function testListPagebyCategory()
    {

    }
}
