<?php

namespace littlemissrobot\ckeditorglossary\web\assets\glossary;

use craft\ckeditor\web\assets\BaseCkeditorPackageAsset;

/**
 * Registers the “Glossary” CKEditor 5 package with Craft’s CKEditor field.
 *
 * The package is loaded only on fields whose toolbar includes the `glossary` button.
 * The built `build/glossary.js` file is produced from `ckeditor5-glossary/` — see that
 * directory’s README for the build command.
 */
class GlossaryBundle extends BaseCkeditorPackageAsset
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/build';

    /**
     * @inheritdoc
     */
    public string $namespace = '@littlemissrobot/ckeditor5-glossary';

    /**
     * @inheritdoc
     */
    public $js = [
        ['glossary.js', 'type' => 'module'],
    ];

    /**
     * @inheritdoc
     */
    public array $pluginNames = [
        'Glossary',
    ];

    /**
     * @inheritdoc
     */
    public array $toolbarItems = [
        'glossary',
    ];
}
