<?php

namespace littlemissrobot\ckeditorglossary\web\twig;

use Craft;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\elements\db\GlossaryTermQuery;
use littlemissrobot\ckeditorglossary\elements\GlossaryTerm;
use littlemissrobot\ckeditorglossary\sources\TermResult;
use littlemissrobot\ckeditorglossary\web\assets\frontend\TooltipAsset;

/**
 * `craft.ckeditorGlossary` template variable.
 */
class GlossaryVariable
{
    /**
     * Registers the front-end tooltip CSS/JS. Call where glossary content renders:
     * `{% do craft.ckeditorGlossary.registerTooltipAssets() %}`
     */
    public function registerTooltipAssets(): void
    {
        TooltipAsset::registerAssets(Craft::$app->getView());
    }

    /**
     * Returns a glossary term query, e.g. `craft.ckeditorGlossary.terms.all()`.
     */
    public function getTerms(): GlossaryTermQuery
    {
        return GlossaryTerm::find();
    }

    /**
     * Resolves a single term by UID for the given (or current) site.
     */
    public function resolve(string $uid, ?int $siteId = null): ?TermResult
    {
        $siteId = $siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        return CkeditorGlossary::getInstance()->getGlossary()->resolve($uid, $siteId);
    }
}
