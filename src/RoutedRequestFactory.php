<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\HttpFoundation\Request;

class RoutedRequestFactory
{
    public function __invoke($query, $request, $attributes, $cookies, $files, $server, $content): Request
    {
        $server['SCRIPT_NAME'] = rtrim(parse_url(get_permalink(), PHP_URL_PATH), '/').$_SERVER['SCRIPT_NAME'];
        $parts = parse_url($server['REQUEST_URI']);
        if ($parts['path'] === dirname($server['SCRIPT_NAME'])) {
            $server['REQUEST_URI'] = $parts['path'].'/';
            if (!empty($parts['query'])) {
                $server['REQUEST_URI'] .= '?'.$parts['query'];
            }
        }
        return RequestFactory::createRequest($query, $request, $attributes, $cookies, $files, $server, $content);
    }
}