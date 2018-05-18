<?php

use Illuminate\Support\Facades\DB;

class JobTest extends TestCase
{
    public function testListJobFeatured()
    {
        $arrFilter = [
            'is_featured' => 1,
            'limit' => 30
        ];

        $result = $this->json('GET', '/v2/jobs', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $flag = true;
            $objResult = json_decode($result->response->getContent());
            foreach ($objResult->result as $objJob) {
                $queryRow = DB::table('lit_job_posting')->where('jp_isfeatured', 1)->where('jp_id', $objJob->id)->first();
                if ($queryRow->jp_isfeatured != 1) {
                    $flag = false;
                    break;
                }
            }
            $result->assertTrue($flag);
        }
    }

    public function testListJobFilter()
    {
        $arrFilter = [
            'sort_by' => 'id',
            'sort_type' => 'DESC',
            'limit' => 30
        ];

        $result = $this->json('GET', '/v2/jobs', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $objResult = json_decode($result->response->getContent());
            $this->assertTrue($this->sortArray($objResult->result, 'id', 'DESC'));
        }
    }

    public function testListAllJob()
    {
        $result = $this->json('GET', '/v2/jobs');
        $result->seeJson(['status' => 'success']);
    }
}
