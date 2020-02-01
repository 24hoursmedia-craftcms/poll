<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll;

use Craft;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use twentyfourhoursmedia\poll\services\ResultService;
use yii\base\Event;
use craft\elements\Entry;
use craft\fields\Matrix;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use twentyfourhoursmedia\poll\services\PollService as PollServiceService;
use twentyfourhoursmedia\poll\services\InstallService;
use twentyfourhoursmedia\poll\variables\PollVariable;
use twentyfourhoursmedia\poll\twigextensions\PollTwigExtension;
use twentyfourhoursmedia\poll\models\Settings;
use twentyfourhoursmedia\poll\utilities\PollUtility as PollUtilityUtility;
use twentyfourhoursmedia\poll\elements\Poll as PollElement;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 *
 * @property  PollService $pollService
 * @property  ResultService $resultService
 * @property  InstallService $installService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Poll extends Plugin
{

    // Static Properties
    // =========================================================================

    public const LOG_CATEGORY = 'poll_plugin';

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Poll::$plugin
     *
     * @var Poll
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Poll::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'installService' => InstallService::class,
            'resultService' => ResultService::class,
        ]);


        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new PollTwigExtension());

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'poll/answer';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
             //   $event->rules['cpActionTrigger1'] = 'poll/download/poll-data';
            }
        );

        // Register Poll elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = PollElement::class;
            }
        );

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = PollUtilityUtility::class;
            }
        );

        // Register our variables

//        Event::on(
//            CraftVariable::class,
//            CraftVariable::EVENT_INIT,
//            function (Event $event) {
//                /** @var CraftVariable $variable */
//                $variable = $event->sender;
//                $variable->set('poll', PollVariable::class);
//            }
//        );


        // Remove poll answer entries if a poll entry is deleted
        Event::on(Entry::class, Entry::EVENT_AFTER_DELETE, static function (\yii\base\Event $event) {
            $service = self::$plugin->pollService;
            if ($service->isAPollEntry($event->sender)) {
                $numRemoved = $service->removeAnswersForPoll($event->sender);
                Craft::warning(sprintf('Removed %d poll submissions because poll entry with ID %d was removed', $numRemoved, $event->sender->id), self::LOG_CATEGORY);
            }
        });

        // Block certain settings on poll answer matrices
        Event::on(Matrix::class, Matrix::EVENT_BEFORE_VALIDATE, static function (\yii\base\ModelEvent $event) {
            $service = self::$plugin->pollService;
            if ($service->isAnAnswerMatrix($event->sender)) {
                $event->isValid = $service->validateAnswerMatrixField($event->sender);
            }
        });

        // Optionally block removal of the plugin to prevent data loss
        $me = $this;
        Event::on(Plugins::class, Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN, static function (PluginEvent $event) use ($me) {
            if ($event->plugin === $me && self::$plugin->settings->blockPluginRemoval) {
                $settingsUrl = UrlHelper::url('settings/plugins/poll');

                echo <<<HTML
<h1>Warning</h1>
<p>You are trying to remove the Poll plugin, but have blocked removal in the settings panel.</p>
<p>Proceed by disabling the block in the <a href="$settingsUrl">plugin settings</a> and then try again.</p>
<p><strong>Because removing the plugin also removes all submitted data, you might want to backup your database first.</strong></p>
HTML;
                die('');
            }

        });

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'poll',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'poll/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }



}
