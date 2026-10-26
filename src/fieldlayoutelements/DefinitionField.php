<?php

namespace littlemissrobot\ckeditorglossary\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use littlemissrobot\ckeditorglossary\elements\GlossaryTerm;

/**
 * The native “Definition” field shown on the glossary term edit screen.
 *
 * The value is stored per-site and is the default source for front-end tooltips.
 */
class DefinitionField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'definition';

    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public bool $translatable = true;

    /**
     * @inheritdoc
     */
    public function attribute(): string
    {
        return 'definition';
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('ckeditor-glossary', 'Definition');
    }

    /**
     * @inheritdoc
     */
    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('ckeditor-glossary', 'Shown in the tooltip when a visitor hovers or focuses the linked term.');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        /** @var GlossaryTerm|null $element */
        return Craft::$app->getView()->renderTemplate('_includes/forms/textarea.twig', [
            'id' => 'definition',
            'name' => 'definition',
            'value' => $element?->definition,
            'disabled' => $static,
            'rows' => 4,
        ]);
    }
}
