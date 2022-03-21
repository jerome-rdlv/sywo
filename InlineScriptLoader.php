<?php


namespace Rdlv\WordPress\Sywo;


use Exception;

class InlineScriptLoader
{
    public static function getTag(string $path, array $args = [])
    {
        return sprintf('<script>%s</script>', self::getScript($path, $args));
    }

    /**
     * @param string $path
     * @param array $args
     * @return string|string[]
     * @throws Exception
     */
    public static function getScript(string $path, array $args = [])
    {
        // try to load minified version if any
        $minPath = preg_replace('/\.([^.]+)$/', '.min.\1', $path);
        if (file_exists($minPath)) {
            $path = $minPath;
        } elseif (!file_exists($path)) {
            throw new Exception(
                sprintf(
                    'File %s does not exists.',
                    $path
                )
            );
        }

        $contents = file_get_contents($path);

        if (!$args) {
            return $contents;
        }

        return sprintf(
            "(function iife(%s){%s})(%s)",
            implode(',', array_keys($args)),
            $contents,
            implode(',', array_map(function ($value) {
                return json_encode($value);
            }, $args))
        );
    }
}