<?php

namespace littlemissrobot\ckeditorglossary\models;

use Craft;
use craft\base\Model;

/**
 * Plugin settings.
 */
class Settings extends Model
{
    /**
     * @var array Per-site settings, keyed by site UID: `['uriFormat' => ?string, 'template' => ?string]`.
     */
    public array $siteSettings = [];

    /**
     * @var string|null A custom field handle to source tooltip HTML from, instead of the native
     * definition. Point this at a CKEditor/rich-text field for rich tooltips. `null` uses the
     * native (plain-text) definition.
     */
    public ?string $tooltipFieldHandle = null;

    /**
     * @var int How long (in seconds) a resolved definition is cached for the front-end API.
     * Cache is also invalidated whenever a term is saved or deleted. `0` = cache until invalidated.
     */
    public int $cacheDuration = 86400;

    /**
     * @var bool Whether the front-end tooltip CSS/JS should be injected automatically on every
     * front-end request. When `false` (default), include it yourself where glossary content renders.
     */
    public bool $autoloadTooltipAssets = false;

    public function defineRules(): array
    {
        return [
            [['cacheDuration'], 'integer', 'min' => 0],
            [['tooltipFieldHandle'], 'string'],
            [['siteSettings'], 'safe'],
        ];
    }

    /**
     * Returns the URI format for the given site, or `null` when terms have no detail page there.
     */
    public function getUriFormat(int $siteId): ?string
    {
        $uriFormat = $this->siteSettingValue($siteId, 'uriFormat');
        return ($uriFormat !== null && $uriFormat !== '') ? $uriFormat : null;
    }

    /**
     * Returns the template used to render a term’s detail page for the given site.
     */
    public function getTemplate(int $siteId): ?string
    {
        $template = $this->siteSettingValue($siteId, 'template');
        return ($template !== null && $template !== '') ? $template : null;
    }

    private function siteSettingValue(int $siteId, string $key): ?string
    {
        $site = Craft::$app->getSites()->getSiteById($siteId);

        if (!$site) {
            return null;
        }

        $value = $this->siteSettings[$site->uid][$key] ?? null;
        return $value !== null ? (string)$value : null;
    }
}
