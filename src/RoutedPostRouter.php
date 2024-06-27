<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class RoutedPostRouter extends Router
{
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $url = parent::generate($name, $parameters, $referenceType);
        if (str_ends_with(get_permalink(), '/')) {
            return $url;
        }
        if ($this->getRouteCollection()->get($name)->getPath() !== '/') {
            return $url;
        }

        // current page has no slash at the end, fix generated url

        $parts = parse_url($url);
        $parts['path'] = rtrim($parts['path'] ?? '', '/');
        return $this->unparse_url($parts);
    }

    private function unparse_url($parsed_url): string
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'].'://' : '';
        $host = $parsed_url['host'] ?? '';
        $port = isset($parsed_url['port']) ? ':'.$parsed_url['port'] : '';
        $user = $parsed_url['user'] ?? '';
        $pass = isset($parsed_url['pass']) ? ':'.$parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parsed_url['path'] ?? '';
        $query = isset($parsed_url['query']) ? '?'.$parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}