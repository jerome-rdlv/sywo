<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\HttpFoundation\Request;

class RequestFactory extends Request
{
    public static function createRequest($query, $request, $attributes, $cookies, $files, $server, $content): Request
    {
        return new Request(
            self::removeMagicQuotes($query),
            self::removeMagicQuotes($request),
            $attributes,
            $cookies,
            $files,
            $server,
            $content
        );
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