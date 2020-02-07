<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\twigextensions;

use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\models\MatrixBlockType;
use twentyfourhoursmedia\poll\models\PollResults;
use twentyfourhoursmedia\poll\Poll;

use Craft;
use twentyfourhoursmedia\poll\services\PollService;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class PollTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'Poll';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('poll_participated', [$this, 'participatedInPoll']),
            new \Twig_SimpleFilter('poll_results', [$this, 'getPollResults']),
            new \Twig_SimpleFilter('poll_uniqid', [$this, 'createUid']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('generatePollAnswerFieldName', [$this, 'generatePollAnswerFieldName']),
            new \Twig_SimpleFunction('generatePollAnswerFieldValue', [$this, 'generatePollAnswerFieldValue']),
            new \Twig_SimpleFunction('pollInputs', [$this, 'getPollInputs'], ['is_safe' => ['html']]),
            // deprecated:
            new \Twig_SimpleFunction('getPollResults', [$this, 'getPollResults']),
            new \Twig_SimpleFunction('pollUid', [$this, 'createUniqid']),
            // deprecated:
            new \Twig_SimpleFunction('getPoll', [$this, 'getPoll']),
        ];
    }

    /**
     * @param Entry $poll
     * @param MatrixBlockQuery null $matrix
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getPollInputs(Entry $poll, $matrix = null): string
    {
        $service = Poll::$plugin->pollService;

        $fieldId = null;
        if ($matrix) {
            $fieldId = $matrix->fieldId;
            $field = Craft::$app->fields->getFieldById($fieldId);
        } else {
            $field = Craft::$app->fields->getFieldByHandle(
                $service->getConfigOption(PollService::CFG_FIELD_ANSWER_MATRIX_HANDLE)
            );
        }
        if (!$field) {
            return 'ERROR invalid answers field';
        }



        $site = Craft::$app->sites->getCurrentSite();
        return <<<HTML
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_SITEID_FIELDNAME)}" value="{$site->id}" />
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_SITEUID_FIELDNAME)}" value="{$site->uid}" />
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_POLLID_FIELDNAME)}" value="{$poll->id}" />
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_POLLUID_FIELDNAME)}" value="{$poll->uid}" />
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_ANSWERFIELDID_FIELDNAME)}" value="{$field->id}" />
        <input type="hidden" name="{$service->getConfigOption(PollService::CFG_FORM_ANSWERFIELDUID_FIELDNAME)}" value="{$field->uid}" />
HTML;
    }

    /**
     * Generates a field name for a poll answer
     * @param MatrixBlockType $answer
     * @return string
     */
    public function generatePollAnswerFieldName(Entry $poll, MatrixBlock $answer): string
    {
        $service = Poll::$plugin->pollService;
        return "{$service->getConfigOption('CFG_FORM_POLLANSWER_FIELDNAME')}[{$poll->uid}]";
    }

    /**
     * Generates a field value for a poll answer
     * @param MatrixBlockType $answer
     * @return string
     */
    public function generatePollAnswerFieldValue(Entry $poll, MatrixBlock $answer): string
    {
        return (string)($answer->uid);
    }

    /**
     * @deprecated
     *
     * @param $poll
     * @return bool
     */
    public function participatedInPoll($poll)
    {
        return Poll::$plugin->pollService->hasParticipated($poll);
    }

    /**
     * @param $pollOrPollId
     * @param array $opts
     * @return PollResults | null
     * @deprecated
     */
    public function getPollResults($pollOrPollId, array $opts = [])
    {
        return Poll::$plugin->facade->getResults($pollOrPollId, $opts);
    }

    /**
     * Creates a uniqid for use in html as element id's etc.
     *
     * @param null $prefix
     * @return string
     */
    public function createUid($prefix = null)
    {
        return uniqid($prefix, false);

    }

    /**
     * Returns a poll regardless wether it is enabled or not
     * @param $id
     * @return Entry|null
     */
    public function getPoll($id)
    {
        return Poll::$plugin->pollService->getPoll($id);
    }
}
