<?php

namespace littlemissrobot\ckeditorglossary\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\User;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\elements\db\GlossaryTermQuery;
use littlemissrobot\ckeditorglossary\fieldlayoutelements\DefinitionField;
use littlemissrobot\ckeditorglossary\Permissions;

/**
 * Glossary term element.
 *
 * @property string|null $definition The per-site term definition.
 */
class GlossaryTerm extends Element
{
    /**
     * @var string|null The per-site term definition (native, translatable attribute).
     */
    public ?string $definition = null;

    public static function displayName(): string
    {
        return Craft::t('ckeditor-glossary', 'Glossary Term');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('ckeditor-glossary', 'glossary term');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('ckeditor-glossary', 'Glossary Terms');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('ckeditor-glossary', 'glossary terms');
    }

    public static function refHandle(): ?string
    {
        return 'glossaryterm';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function hasDrafts(): bool
    {
        return true;
    }

    public function hasRevisions(): bool
    {
        return true;
    }

    public static function find(): GlossaryTermQuery
    {
        return Craft::createObject(GlossaryTermQuery::class, [static::class]);
    }

    protected static function defineSources(?string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('ckeditor-glossary', 'All terms'),
                'criteria' => [],
                'defaultSort' => ['title', 'asc'],
            ],
        ];
    }

    protected static function defineActions(?string $source = null): array
    {
        $elementsService = Craft::$app->getElements();
        $actions = [];

        $actions[] = SetStatus::class;

        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('ckeditor-glossary', 'Are you sure you want to delete the selected glossary terms?'),
            'successMessage' => Craft::t('ckeditor-glossary', 'Glossary terms deleted.'),
        ]);

        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('ckeditor-glossary', 'Glossary terms restored.'),
            'partialSuccessMessage' => Craft::t('ckeditor-glossary', 'Some glossary terms restored.'),
            'failMessage' => Craft::t('ckeditor-glossary', 'Glossary terms not restored.'),
        ]);

        return $actions;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'definition'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'definition' => ['label' => Craft::t('ckeditor-glossary', 'Definition')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['definition', 'link', 'dateUpdated'];
    }

    public function getUriFormat(): ?string
    {
        return CkeditorGlossary::getInstance()->getSettings()->getUriFormat($this->siteId);
    }

    protected function route(): array|string|null
    {
        if (!$this->previewing && $this->getStatus() !== self::STATUS_ENABLED) {
            return null;
        }

        if (!$this->getUriFormat()) {
            return null;
        }

        $template = CkeditorGlossary::getInstance()->getSettings()->getTemplate($this->siteId);

        if (!$template) {
            return null;
        }

        return [
            'templates/render',
            [
                'template' => $template,
                'variables' => [
                    'term' => $this,
                ],
            ],
        ];
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return self::fieldLayout();
    }

    /**
     * Returns the configured glossary term field layout, falling back to a default
     * (Title + Definition) when an admin hasn’t saved one yet.
     */
    public static function fieldLayout(): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(self::class);

        if (empty($fieldLayout->getTabs())) {
            $tab = new FieldLayoutTab(['name' => Craft::t('app', 'Content')]);
            $tab->setElements([
                new TitleField(['label' => Craft::t('ckeditor-glossary', 'Term')]),
                new DefinitionField(),
            ]);
            $fieldLayout->setTabs([$tab]);
        }

        return $fieldLayout;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['definition'], 'string'];
        $rules[] = [['definition'], 'required', 'on' => [self::SCENARIO_LIVE]];
        return $rules;
    }

    public function getSupportedSites(): array
    {
        // Localized: available on every site. Editors translate the definition per site.
        return array_map(
            fn($siteId) => ['siteId' => $siteId],
            Craft::$app->getSites()->getAllSiteIds(),
        );
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            Db::upsert('{{%ckeditorglossary_terms}}', [
                'id' => $this->id,
                'siteId' => $this->siteId,
                'definition' => $this->definition,
            ], [
                'definition' => $this->definition,
            ]);
        } else {
            // Propagated save to another site — seed the definition only if that site has no
            // row yet, so an existing per-site translation is never overwritten.
            Db::upsert('{{%ckeditorglossary_terms}}', [
                'id' => $this->id,
                'siteId' => $this->siteId,
                'definition' => $this->definition,
            ], false);
        }

        parent::afterSave($isNew);
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'definition') {
            $value = (string)$this->definition;
            return Html::encode(StringHelper::safeTruncate($value, 100, '…'));
        }

        return parent::attributeHtml($attribute);
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('ckeditor-glossary/terms/' . $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('ckeditor-glossary/terms');
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can(Permissions::MANAGE_TERMS);
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $user->can(Permissions::MANAGE_TERMS);
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can(Permissions::MANAGE_TERMS);
    }

    public function canCreateDrafts(User $user): bool
    {
        return $this->canSave($user);
    }
}
