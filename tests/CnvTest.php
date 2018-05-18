<?php
namespace Tests;

class CnvTest
{
    public function dump($function = 'var_export', $json_decode = true) {
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
}