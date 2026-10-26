<?php

namespace littlemissrobot\ckeditorglossary\sources;

/**
 * A resolved glossary term, independent of the backing storage (element, category, …).
 */
class TermResult
{
    public function __construct(
        public readonly string $uid,
        public readonly string $name,
        public readonly string $definitionHtml,
        public readonly ?string $url,
        public readonly int $siteId,
        public readonly string $siteHandle,
        public readonly string $siteName,
        public readonly string $language,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'definition' => $this->definitionHtml,
            'url' => $this->url,
            'siteId' => $this->siteId,
            'siteHandle' => $this->siteHandle,
            'siteName' => $this->siteName,
            'language' => $this->language,
        ];
    }
}
