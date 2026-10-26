<?php

namespace littlemissrobot\ckeditorglossary\sources;

use Craft;
use craft\base\Element;
use craft\helpers\Html;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\elements\GlossaryTerm;

/**
 * The default glossary source, backed by the plugin’s {@see GlossaryTerm} element.
 */
class ElementTermSource implements GlossarySourceInterface
{
    public function search(string $query, int $siteId, int $limit = 20): array
    {
        $termQuery = GlossaryTerm::find()
            ->siteId($siteId)
            ->status(Element::STATUS_ENABLED)
            ->limit($limit)
            ->orderBy(['title' => SORT_ASC]);

        $query = trim($query);
        if ($query !== '') {
            $termQuery->title('*' . str_replace(['*', ','], '', $query) . '*');
        }

        return array_map(
            fn(GlossaryTerm $term) => $this->toResult($term),
            $termQuery->all(),
        );
    }

    public function resolve(string $uid, int $siteId): ?TermResult
    {
        $term = GlossaryTerm::find()
            ->uid($uid)
            ->siteId($siteId)
            ->status(Element::STATUS_ENABLED)
            ->one();

        return $term ? $this->toResult($term) : null;
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function createTerm(string $name, string $definition, int $siteId): TermResult
    {
        $term = new GlossaryTerm();
        $term->siteId = $siteId;
        $term->title = $name;
        $term->definition = $definition;
        $term->enabled = true;

        if (!Craft::$app->getElements()->saveElement($term)) {
            throw new \RuntimeException(
                'Could not save glossary term: ' . implode(', ', $term->getFirstErrors()),
            );
        }

        return $this->toResult($term);
    }

    private function toResult(GlossaryTerm $term): TermResult
    {
        $site = $term->getSite();

        return new TermResult(
            uid: $term->uid,
            name: (string)$term->title,
            definitionHtml: $this->definitionHtml($term),
            url: $term->getUrl(),
            siteId: $term->siteId,
            siteHandle: $site->handle,
            siteName: $site->name,
            language: $site->language,
        );
    }

    private function definitionHtml(GlossaryTerm $term): string
    {
        $handle = CkeditorGlossary::getInstance()->getSettings()->tooltipFieldHandle;

        if ($handle) {
            $value = $term->getFieldValue($handle);
            // Rich-text / CKEditor fields stringify to trusted author HTML.
            return trim((string)$value);
        }

        // Native plain-text definition — escape, then preserve line breaks.
        return nl2br(Html::encode((string)$term->definition), false);
    }
}
