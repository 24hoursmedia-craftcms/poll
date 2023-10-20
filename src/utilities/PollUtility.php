<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\utilities;

use twentyfourhoursmedia\poll\models\SetupReport;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\assetbundles\pollutilityutility\PollUtilityUtilityAsset;

use Craft;
use craft\base\Utility;
use twentyfourhoursmedia\poll\services\PollService;
use yii\web\ForbiddenHttpException;

/**
 * poll Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class PollUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this utility.
     *
     * @return string The display name of this utility.
     */
    public static function displayName(): string
    {
        return Craft::t('poll', 'Poll');
    }

    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     *
     * @return string
     */
    public static function id(): string
    {
        return 'poll-poll-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias("@twentyfourhoursmedia/poll/assetbundles/pollutilityutility/dist/img/PollUtility-icon.svg");
    }

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     *
     * @return int
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * Returns the utility's content HTML.
     *
     * @return string
     */
    public static function contentHtml(): string
    {
        if (!Craft::$app->getUser()->getIsAdmin()) {
            throw new ForbiddenHttpException('User is not permitted to perform this action.');
        }


        Craft::$app->getView()->registerAssetBundle(PollUtilityUtilityAsset::class);

        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
        $installService  = Poll::getInstance()->installService;
        $pollService = Poll::getInstance()->pollService;
        $setupReport = new SetupReport();
        $setupOk = $installService->check( $setupReport);

        return Craft::$app->getView()->renderTemplate(
            'poll/_components/utilities/PollUtility_content',
            [
                'allow_admin_changes' => $allowAdminChanges,
                'setup_ok' => $setupOk,
                'setup_report' => $setupReport,
                'selectPollFieldHandle' => $pollService->getConfigOption(PollService::CFG_FIELD_SELECT_POLL_HANDLE)
            ]
        );
    }
}
