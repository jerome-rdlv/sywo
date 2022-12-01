<?php

namespace Rdlv\WordPress\Sywo;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    public static function createFromGlobals()
    {
        // Remove WordPress magic quotes
        isset($_GET) && $_GET = self::removeMagicQuotes($_GET);
        isset($_POST) && $_POST = self::removeMagicQuotes($_POST);

        $request = parent::createFromGlobals();
//        $request->requestUri = $_SERVER['REQUEST_URI'];
        $request->baseUrl = rtrim(parse_url(get_permalink(), PHP_URL_PATH), '/');
        $request->setLocale(get_locale());

        return $request;
    }

    /**
     * @param $array
     * @return mixed
     * @see wp-includes/functions.php:add_magic_quotes()
     */
    private static function removeMagicQuotes($array)
    {
        foreach ((array)$array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = self::removeMagicQuotes($v);
            } elseif (is_string($v)) {
                $array[$k] = stripslashes($v);
            }
        }

        return $array;
    }
}