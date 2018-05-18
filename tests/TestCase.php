<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    public function dump($function = 'var_export', $json_decode = true)
    {
        $content = $this->response->getContent();
        if ($json_decode) {
            $content = json_decode($content, true);
        }
        // ❤ ✓ ☀ ★ ☆ ☂ ♞ ☯ ☭ € ☎ ∞ ❄ ♫ ₽ ☼
        $seperator = '❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤ ❤';
        echo PHP_EOL . $seperator . PHP_EOL;
        $function($content);
        echo $seperator . PHP_EOL;
        return $this;
    }

    public function sortArray($arrSort = [], $sort_by, $sort_type)
    {
        $flag = true;
        switch ($sort_type) {
            case 'ASC':
                for ($i = 1; $i < count($arrSort); $i++) {
                    if ($arrSort[$i]->$sort_by < $arrSort[$i-1]->$sort_by) {
                        $flag = false;
                        break;
                    }
                }
                break;
            case 'DESC':
                for ($i = 1; $i < count($arrSort); $i++) {
                    if ($arrSort[$i]->$sort_by > $arrSort[$i-1]->$sort_by) {
                        $flag = false;
                        break;
                    }
                }
                break;
            default:
                $flag = false;
        }
        return $flag;
    }
}
