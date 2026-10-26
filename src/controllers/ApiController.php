<?php

namespace littlemissrobot\ckeditorglossary\controllers;

use Craft;
use craft\web\Controller;
use littlemissrobot\ckeditorglossary\CkeditorGlossary;
use yii\web\Response;

/**
 * Public front-end API consumed by the tooltip script.
 */
class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = ['definition' => self::ALLOW_ANONYMOUS_LIVE];

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * Returns the resolved definition for a single term (by UID) for the requested site.
     */
    public function actionDefinition(): Response
    {
        $request = Craft::$app->getRequest();
        $uid = (string)$request->getParam('uid', '');
        $siteId = $this->resolveSiteId((string)$request->getParam('site', ''));

        if ($uid === '') {
            return $this->asJson(['error' => 'Missing term reference.'])->setStatusCode(400);
        }

        $payload = CkeditorGlossary::getInstance()->getGlossary()->getDefinitionPayload($uid, $siteId);

        if ($payload === null) {
            return $this->asJson(['error' => 'Term not found.'])->setStatusCode(404);
        }

        $response = $this->asJson($payload);
        // Allow shared/CDN caching; the plugin invalidates its own data cache on term changes.
        $response->headers->set('Cache-Control', 'public, max-age=300');

        return $response;
    }

    private function resolveSiteId(string $site): int
    {
        $sites = Craft::$app->getSites();

        if ($site !== '') {
            $resolved = is_numeric($site)
                ? $sites->getSiteById((int)$site)
                : $sites->getSiteByHandle($site);

            if ($resolved) {
                return $resolved->id;
            }
        }

        return $sites->getCurrentSite()->id;
    }
}
