<?php

namespace littlemissrobot\ckeditorglossary\controllers;

use Craft;
use craft\web\Controller;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use littlemissrobot\ckeditorglossary\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Control-panel endpoints used by the CKEditor glossary plugin (autocomplete + inline term creation).
 */
class TermsController extends Controller
{
    /**
     * Autocomplete search for the glossary balloon.
     */
    public function actionSearch(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission(Permissions::LINK_TERMS);

        $request = Craft::$app->getRequest();
        $query = (string)$request->getParam('q', '');
        $siteId = $this->resolveSiteId((int)$request->getParam('siteId', 0));

        $results = array_map(
            fn($term) => [
                'uid' => $term->uid,
                'name' => $term->name,
                'definition' => $term->definitionHtml,
                'siteName' => $term->siteName,
                'language' => $term->language,
            ],
            CkeditorGlossary::getInstance()->getGlossary()->search($query, $siteId),
        );

        return $this->asJson(['terms' => $results]);
    }

    /**
     * Creates a term on the fly from within the editor.
     */
    public function actionCreateInline(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(Permissions::MANAGE_TERMS);

        $request = Craft::$app->getRequest();
        $name = trim((string)$request->getRequiredBodyParam('name'));
        $definition = (string)$request->getBodyParam('definition', '');
        $siteId = $this->resolveSiteId((int)$request->getBodyParam('siteId', 0));

        if ($name === '') {
            throw new BadRequestHttpException('A term name is required.');
        }

        $source = CkeditorGlossary::getInstance()->getGlossary()->getSource();

        if (!$source->canCreate()) {
            return $this->asFailure(Craft::t('ckeditor-glossary', 'This glossary source does not support creating terms.'));
        }

        try {
            $term = $source->createTerm($name, $definition, $siteId);
        } catch (\Throwable $e) {
            return $this->asFailure($e->getMessage());
        }

        return $this->asJson([
            'success' => true,
            'term' => [
                'uid' => $term->uid,
                'name' => $term->name,
                'definition' => $term->definitionHtml,
                'siteName' => $term->siteName,
                'language' => $term->language,
            ],
        ]);
    }

    private function resolveSiteId(int $siteId): int
    {
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
            if ($site) {
                return $site->id;
            }
        }

        return Craft::$app->getSites()->getCurrentSite()->id;
    }
}
