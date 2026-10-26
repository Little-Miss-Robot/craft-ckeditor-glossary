<?php

namespace littlemissrobot\ckeditorglossary\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

/**
 * Glossary term query.
 *
 * @method \littlemissrobot\ckeditorglossary\elements\GlossaryTerm[] all($db = null)
 * @method \littlemissrobot\ckeditorglossary\elements\GlossaryTerm|null one($db = null)
 * @method \littlemissrobot\ckeditorglossary\elements\GlossaryTerm|null nth(int $n, $db = null)
 */
class GlossaryTermQuery extends ElementQuery
{
    /**
     * @var mixed The term definition that resulting terms must have.
     */
    public mixed $definition = null;

    /**
     * Narrows the query results based on the terms’ definitions.
     */
    public function definition(mixed $value): static
    {
        $this->definition = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // The per-site definition lives in our own table, keyed by element id + site id.
        $this->query->leftJoin(
            ['ckeditorglossary_terms' => '{{%ckeditorglossary_terms}}'],
            '[[ckeditorglossary_terms.id]] = [[elements.id]] AND [[ckeditorglossary_terms.siteId]] = [[elements_sites.siteId]]',
        );

        $this->query->addSelect(['ckeditorglossary_terms.definition']);

        if ($this->definition !== null) {
            $this->subQuery->leftJoin(
                ['ckeditorglossary_terms' => '{{%ckeditorglossary_terms}}'],
                '[[ckeditorglossary_terms.id]] = [[elements.id]] AND [[ckeditorglossary_terms.siteId]] = [[elements_sites.siteId]]',
            );
            $this->subQuery->andWhere(Db::parseParam('ckeditorglossary_terms.definition', $this->definition));
        }

        return parent::beforePrepare();
    }
}
