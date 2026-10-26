<?php

namespace littlemissrobot\ckeditorglossary\controllers;

use Craft;
use craft\web\Controller;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\elements\GlossaryTerm;
use littlemissrobot\ckeditorglossary\models\Settings;
use yii\web\Response;

/**
 * Plugin settings screen (general options, per-site detail pages, and the term field layout).
 */
class SettingsController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAdmin();

        return true;
    }

    public function actionIndex(): Response
    {
        /** @var Settings $settings */
        $settings = CkeditorGlossary::getInstance()->getSettings();

        return $this->renderTemplate('ckeditor-glossary/settings', [
            'plugin' => CkeditorGlossary::getInstance(),
            'settings' => $settings,
            'fieldLayout' => GlossaryTerm::fieldLayout(),
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $plugin = CkeditorGlossary::getInstance();
        $request = Craft::$app->getRequest();

        /** @var Settings $settings */
        $settings = $plugin->getSettings();
        $settings->tooltipFieldHandle = $request->getBodyParam('tooltipFieldHandle') ?: null;
        $settings->cacheDuration = (int)$request->getBodyParam('cacheDuration', $settings->cacheDuration);
        $settings->autoloadTooltipAssets = (bool)$request->getBodyParam('autoloadTooltipAssets', false);
        $settings->siteSettings = $request->getBodyParam('siteSettings', []);

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray())) {
            Craft::$app->getSession()->setError(Craft::t('ckeditor-glossary', 'Couldn’t save settings.'));
            Craft::$app->getUrlManager()->setRouteParams(['settings' => $settings]);
            return null;
        }

        // The field layout is stored by element type (not in project-config settings).
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = GlossaryTerm::class;
        Craft::$app->getFields()->saveLayout($fieldLayout);

        Craft::$app->getSession()->setNotice(Craft::t('ckeditor-glossary', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
