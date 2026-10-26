<?php

namespace littlemissrobot\ckeditorglossary;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\ckeditor\Field as CkeditorField;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\UrlHelper;
use craft\htmlfield\events\ModifyPurifierConfigEvent;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use littlemissrobot\ckeditorglossary\elements\GlossaryTerm;
use littlemissrobot\ckeditorglossary\fieldlayoutelements\DefinitionField;
use littlemissrobot\ckeditorglossary\models\Settings;
use littlemissrobot\ckeditorglossary\services\Glossary;
use littlemissrobot\ckeditorglossary\web\assets\frontend\TooltipAsset;
use littlemissrobot\ckeditorglossary\web\assets\glossary\GlossaryBundle;
use littlemissrobot\ckeditorglossary\web\twig\GlossaryVariable;
use yii\base\Event;

/**
 * CKEditor Glossary plugin.
 *
 * @method Settings getSettings()
 * @property-read Glossary $glossary
 */
class CkeditorGlossary extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'glossary' => ['class' => Glossary::class],
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->_registerCkeditorPackage();
        $this->_registerElementType();
        $this->_registerPermissions();
        $this->_registerCpRoutes();
        $this->_registerNativeFields();
        $this->_registerPurifierConfig();
        $this->_registerCacheInvalidation();
        $this->_registerVariable();
        $this->_registerFrontendAssets();
    }

    public function getGlossary(): Glossary
    {
        /** @var Glossary $glossary */
        $glossary = $this->get('glossary');
        return $glossary;
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('ckeditor-glossary/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('ckeditor-glossary', 'Glossary');
        // Point at the plugin’s section root so every ckeditor-glossary/* page keeps the primary
        // nav item selected and its subnav expanded. The root route renders the terms index.
        $item['url'] = 'ckeditor-glossary';

        $subnav = [
            'terms' => [
                'label' => Craft::t('ckeditor-glossary', 'Terms'),
                'url' => 'ckeditor-glossary/terms',
            ],
        ];

        if (Craft::$app->getUser()->getIsAdmin()) {
            $subnav['settings'] = [
                'label' => Craft::t('app', 'Settings'),
                'url' => 'ckeditor-glossary/settings',
            ];
        }

        $item['subnav'] = $subnav;

        return $item;
    }

    private function _registerCkeditorPackage(): void
    {
        // Load-order safe: registering from onInit means it doesn’t matter whether the CKEditor
        // plugin has booted yet.
        Craft::$app->onInit(function() {
            \craft\ckeditor\Plugin::registerCkeditorPackage(GlossaryBundle::class, 'glossary.js');
        });
    }

    private function _registerElementType(): void
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = GlossaryTerm::class;
            },
        );
    }

    private function _registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('ckeditor-glossary', 'CKEditor Glossary'),
                    'permissions' => [
                        Permissions::MANAGE_TERMS => [
                            'label' => Craft::t('ckeditor-glossary', 'Administer glossary terms'),
                        ],
                        Permissions::LINK_TERMS => [
                            'label' => Craft::t('ckeditor-glossary', 'Link to glossary terms'),
                        ],
                    ],
                ];
            },
        );
    }

    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['ckeditor-glossary'] = ['template' => 'ckeditor-glossary/terms/_index'];
                $event->rules['ckeditor-glossary/terms'] = ['template' => 'ckeditor-glossary/terms/_index'];
                $event->rules['ckeditor-glossary/terms/<elementId:\d+>'] = 'elements/edit';
                $event->rules['ckeditor-glossary/settings'] = 'ckeditor-glossary/settings/index';
            },
        );
    }

    private function _registerNativeFields(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function(DefineFieldLayoutFieldsEvent $event) {
                if ($event->sender->type === GlossaryTerm::class) {
                    $event->fields[] = TitleField::class;
                    $event->fields[] = DefinitionField::class;
                }
            },
        );
    }

    private function _registerPurifierConfig(): void
    {
        Event::on(
            CkeditorField::class,
            CkeditorField::EVENT_MODIFY_PURIFIER_CONFIG,
            function(ModifyPurifierConfigEvent $event) {
                $def = $event->config->getHTMLDefinition(true);
                if ($def !== null) {
                    $def->addAttribute('a', 'data-glossary-uid', 'Text');
                }
            },
        );
    }

    private function _registerCacheInvalidation(): void
    {
        $invalidate = function() {
            $this->getGlossary()->invalidateCache();
        };

        Event::on(GlossaryTerm::class, GlossaryTerm::EVENT_AFTER_SAVE, $invalidate);
        Event::on(GlossaryTerm::class, GlossaryTerm::EVENT_AFTER_DELETE, $invalidate);
        Event::on(GlossaryTerm::class, GlossaryTerm::EVENT_AFTER_RESTORE, $invalidate);
    }

    private function _registerVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('ckeditorGlossary', GlossaryVariable::class);
            },
        );
    }

    private function _registerFrontendAssets(): void
    {
        if (!$this->getSettings()->autoloadTooltipAssets) {
            return;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest() || !Craft::$app->getRequest()->getIsSiteRequest()) {
            return;
        }

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function() {
                TooltipAsset::registerAssets(Craft::$app->getView());
            },
        );
    }
}
