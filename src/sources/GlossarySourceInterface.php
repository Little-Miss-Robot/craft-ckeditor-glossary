<?php

namespace littlemissrobot\ckeditorglossary\sources;

/**
 * A glossary source supplies terms for autocomplete, resolves a stored reference back to a term,
 * and (optionally) creates new terms on the fly.
 *
 * Links are stored in content as an element-type-agnostic UID, so swapping or adding sources later
 * (categories, entries, …) does not change previously saved markup.
 */
interface GlossarySourceInterface
{
    /**
     * Searches terms matching the query for the given site, for editor autocomplete.
     *
     * @return TermResult[]
     */
    public function search(string $query, int $siteId, int $limit = 20): array;

    /**
     * Resolves a stored term reference (UID) to a term for the given site, or `null` if it no
     * longer exists / isn’t available there.
     */
    public function resolve(string $uid, int $siteId): ?TermResult;

    /**
     * Whether this source supports creating terms from within the editor.
     */
    public function canCreate(): bool;

    /**
     * Creates a new term and returns it. Throws if creation fails or is unsupported.
     *
     * @throws \Throwable
     */
    public function createTerm(string $name, string $definition, int $siteId): TermResult;
}
