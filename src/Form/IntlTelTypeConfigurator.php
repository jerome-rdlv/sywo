<?php


namespace Rdlv\WordPress\Sywo\Form;


use Rdlv\WordPress\Sywo\InlineScriptLoader;

class IntlTelTypeConfigurator
{
    public function __invoke(IntlTelType $type)
    {
        $baseUrl = plugin_dir_url(__DIR__);
        $intlUrl = $baseUrl . 'node_modules/web/intl-tel-input/build/js';
        $basePath = plugin_dir_path(__DIR__);
        $script = InlineScriptLoader::getScript($basePath . 'assets/intl-tel-input.js', [
            'intl_url' => $intlUrl,
        ]);
        if ($script) {
            wp_enqueue_script('intl-tel-input', $intlUrl . '/intlTelInput.js', [], false, true);
            wp_add_inline_script('intl-tel-input', $script);
        }
    }
}