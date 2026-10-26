<?php

namespace littlemissrobot\ckeditorglossary\web\assets\frontend;

use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * Front-end tooltip CSS + JS for glossary links.
 *
 * Register it where glossary content renders with:
 * `{% do craft.ckeditorGlossary.registerTooltipAssets() %}`
 * (or enable “Autoload tooltip assets” in the plugin settings).
 */
class TooltipAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/resources';

    public $depends = [];

    public $js = [
        'tooltip.js',
    ];

    public $css = [
        'tooltip.css',
    ];

    /**
     * Registers the tooltip bundle and exposes the API endpoint to the script.
     */
    public static function registerAssets(View $view): void
    {
        $view->registerAssetBundle(self::class);
        $view->registerJs(
            sprintf('window.ckeditorGlossaryEndpoint=%s;', Json::encode(UrlHelper::actionUrl('ckeditor-glossary/api/definition'))),
            View::POS_HEAD,
        );
    }
}
