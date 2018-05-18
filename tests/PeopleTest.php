<?php

use Illuminate\Support\Facades\DB;

class PeopleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPeopleIsFeatured()
    {

        $arrFilter = [
        'is_featured' => 1,
        'limit' => 30
        ];

        $result = $this->json('GET', '/v2/peoples', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $flag = true;
            $objResult = json_decode($result->response->getContent());
            foreach ($objResult->result as $objPeople) {
                $queryRow = DB::table('lit_people')->where('p_isfeatured', 1)->where('p_id', $objPeople->id)->first();
                if ($queryRow->p_isfeatured != 1) {
                    $flag = false;
                    break;
                }
            }
            $result->assertTrue($flag);
        }
    }

    public function testListPeopleHightViewed()
    {
        $arrFilter = [
            'sort_by' => 'countview',
            'sort_type' => 'DESC',
            'limit' => 30
        ];

        $result = $this->json('GET', '/v2/peoples', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $objResult = json_decode($result->response->getContent());
            $this->assertTrue($this->sortArray($objResult->result, 'countview', 'DESC'));
        }
    }

    public function testListPeopleHightRating()
    {
        $arrFilter = [
            'high_rating' => 1,
            'sort_type' => 'DESC',
            'limit' => 30
        ];

        $result = $this->json('GET', '/v2/peoples', $arrFilter);

        if ($result->seeJson(['status' => 'success'])) {
            $objResult = json_decode($result->response->getContent());
            $this->assertTrue($this->sortArray($objResult->result, 'avg_rating', 'DESC'));
        }
    }

    public function testPeopleDetail()
    {
        $id_people = 0;
        switch ($id_people) {
            case '0': //Not found candidate id = 0
                $result = $this->json('GET', '/v2/peoples/0');
                $result->seeJson(['status' => 'error']);
            case '200':
                //found candidate id = 200
                $result = $this->json('GET', '/v2/peoples/200');
                $result->seeJson(['status' => 'success']);
            default:
                break;
        }
    }

    public function testListPeopleCategory()
    {
        $result = $this->json('GET', '/v2/peoples');
        $this->seeJson(['status' => 'success']);
    }

}
