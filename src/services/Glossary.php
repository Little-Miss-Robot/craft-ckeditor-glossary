<?php

namespace littlemissrobot\ckeditorglossary\services;

use Craft;
use craft\helpers\StringHelper;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\sources\ElementTermSource;
use littlemissrobot\ckeditorglossary\sources\GlossarySourceInterface;
use littlemissrobot\ckeditorglossary\sources\TermResult;
use yii\base\Component;
use yii\caching\TagDependency;

/**
 * Glossary service — the single entry point for term lookups.
 *
 * All access goes through the configured {@see GlossarySourceInterface}, so additional sources
 * (categories, entries, …) can be introduced later without touching callers or stored content.
 */
class Glossary extends Component
{
    public const CACHE_TAG = 'ckeditor-glossary-terms';

    private ?GlossarySourceInterface $source = null;

    /**
     * Returns the active glossary source. v1 ships only the element-backed source.
     */
    public function getSource(): GlossarySourceInterface
    {
        if ($this->source === null) {
            $this->source = Craft::createObject(ElementTermSource::class);
        }

        return $this->source;
    }

    /**
     * Allows a source to be injected (tests, future configurable sources).
     */
    public function setSource(GlossarySourceInterface $source): void
    {
        $this->source = $source;
    }

    /**
     * @return TermResult[]
     */
    public function search(string $query, int $siteId, int $limit = 20): array
    {
        return $this->getSource()->search($query, $siteId, $limit);
    }

    public function resolve(string $uid, int $siteId): ?TermResult
    {
        return $this->getSource()->resolve($uid, $siteId);
    }

    /**
     * Returns the cached JSON payload for a single term, as consumed by the front-end tooltip API.
     *
     * @return array<string,mixed>|null
     */
    public function getDefinitionPayload(string $uid, int $siteId): ?array
    {
        $cache = Craft::$app->getCache();
        $key = $this->cacheKey($uid, $siteId);
        $cached = $cache->get($key);

        if ($cached !== false) {
            return $cached ?: null;
        }

        $term = $this->resolve($uid, $siteId);
        $payload = $term?->toArray();

        $duration = CkeditorGlossary::getInstance()->getSettings()->cacheDuration;
        $cache->set(
            $key,
            $payload ?? [],
            $duration > 0 ? $duration : null,
            new TagDependency(['tags' => [self::CACHE_TAG]]),
        );

        return $payload;
    }

    /**
     * Invalidates all cached definitions (called when any term is saved or deleted).
     */
    public function invalidateCache(): void
    {
        TagDependency::invalidate(Craft::$app->getCache(), self::CACHE_TAG);
    }

    private function cacheKey(string $uid, int $siteId): string
    {
        return sprintf('ckeditor-glossary:def:%d:%s', $siteId, StringHelper::slugify($uid));
    }
}
