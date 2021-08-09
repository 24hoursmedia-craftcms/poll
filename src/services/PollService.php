<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\services;

use Craft;
use yii\base\Component;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\models\Section;
use twentyfourhoursmedia\poll\events\PollEvents;
use twentyfourhoursmedia\poll\events\PollSubmittedEvent;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\records\PollAnswer;
use yii\base\InvalidConfigException;
use yii\web\Cookie;

/**
 * PollService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class PollService extends Component
{

    // constants to refer to configuration keys
    public const CFG_POLL_SECTION_HANDLE = 'CFG_POLL_SECTION_HANDLE';
    public const CFG_FIELD_GROUP_NAME = 'CFG_FIELD_GROUP_NAME';
    public const CFG_FIELD_SELECT_POLL_HANDLE = 'CFG_FIELD_SELECT_POLL_HANDLE';
    public const CFG_FIELD_ANSWER_MATRIX_HANDLE = 'CFG_FIELD_ANSWER_MATRIX_HANDLE';
    public const CFG_MATRIXBLOCK_ANSWER_HANDLE = 'CFG_MATRIXBLOCK_ANSWER_HANDLE';

    public const CFG_FORM_POLLID_FIELDNAME = "CFG_FORM_POLLID_FIELDNAME";
    public const CFG_FORM_POLLUID_FIELDNAME = "CFG_FORM_POLLUID_FIELDNAME";
    public const CFG_FORM_POLLANSWER_FIELDNAME = "CFG_FORM_POLLANSWER_FIELDNAME";
    public const CFG_FORM_POLLANSWERTEXT_FIELDNAME = "CFG_FORM_POLLANSWERTEXT_FIELDNAME";
    public const CFG_FORM_SITEID_FIELDNAME = "CFG_FORM_SITEID_FIELDNAME";
    public const CFG_FORM_SITEUID_FIELDNAME = "CFG_FORM_SITEUID_FIELDNAME";
    public const CFG_FORM_ANSWERFIELDID_FIELDNAME = "CFG_FORM_ANSWERSFIELDID_FIELDNAME";
    public const CFG_FORM_ANSWERFIELDUID_FIELDNAME = "CFG_FORM_ANSWERSFIELDUID_FIELDNAME";

    private $config = [
        // section, fieldtype, .. handles
        self::CFG_POLL_SECTION_HANDLE => 'pollSection',
        self::CFG_FIELD_ANSWER_MATRIX_HANDLE => 'pollAnswerMatrix',
        self::CFG_MATRIXBLOCK_ANSWER_HANDLE => 'answer',
        self::CFG_FIELD_SELECT_POLL_HANDLE => 'selectedPoll',

        // fieldgroup where polls are placed in
        self::CFG_FIELD_GROUP_NAME => 'Poll',

        // form field names
        self::CFG_FORM_SITEID_FIELDNAME => '__site_id',
        self::CFG_FORM_SITEUID_FIELDNAME => '__site_uid',
        self::CFG_FORM_POLLID_FIELDNAME => '__poll_id',
        self::CFG_FORM_POLLUID_FIELDNAME => '__poll_uid',
        self::CFG_FORM_ANSWERFIELDID_FIELDNAME => '__answerfield_id',
        self::CFG_FORM_ANSWERFIELDUID_FIELDNAME => '__answerfield_uid',
        self::CFG_FORM_POLLANSWER_FIELDNAME => '__answer',
        self::CFG_FORM_POLLANSWERTEXT_FIELDNAME => '__text'
    ];


    public function __construct($config = [])
    {
        parent::__construct($config);

        $settings = Poll::$plugin->getSettings();
        $this
            ->applyConfig(self::CFG_POLL_SECTION_HANDLE, $settings->sectionHandle)
            ->applyConfig(self::CFG_FIELD_ANSWER_MATRIX_HANDLE, $settings->answerMatrixFieldHandle)
            ->applyConfig(self::CFG_FIELD_SELECT_POLL_HANDLE, $settings->selectPollFieldHandle)
            ->applyConfig(self::CFG_MATRIXBLOCK_ANSWER_HANDLE, $settings->matrixBlockAnswerHandle);

    }


    /**
     * Sets a value in $config if val does not evaluate to an empty string
     * @param $key
     * @param $val
     * @return $this
     */
    private function applyConfig($key, $val): self
    {
        $val = trim($val);
        if ('' === $val) {
            return $this;
        }
        $this->config[$key] = $val;
        return $this;
    }


    /**
     * @return array = $this->config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getConfigOption($handle)
    {
        return $this->config[$handle];
    }

    public function getCookiePollIds()
    {
        $participatedPolls = explode(',', Craft::$app->request->cookies['_pollids'] ?? '');
        $participatedPolls = array_map('intval', $participatedPolls);
        $participatedPolls = array_filter($participatedPolls);
        return $participatedPolls;
    }

    /**
     * Adds a poll id to a cookie to keep track of anonymous participations
     *
     * @param $pollId
     */
    public function addPollIdToCookie($pollId)
    {
        $settings = Poll::$plugin->settings;
        $cookiePollIds = $this->getCookiePollIds();
        array_unshift($cookiePollIds, $pollId);
        $cookiePollIds = array_slice($cookiePollIds, 0, $settings->numCookieParticipations);
        $cookiePollIds = array_values(array_unique($cookiePollIds));
        $cookie = new Cookie([
            'name' => '_pollids',
            'value' => implode(',', $cookiePollIds),
            'expire' => time() + 86400 * $settings->participationsCookieLifetime
        ]);
        Craft::$app->getResponse()->cookies->add($cookie);
    }

    public function hasParticipated($pollOrPollId, $user = null)
    {
        $pollId = $pollOrPollId instanceof Entry ? $pollOrPollId->id : $pollOrPollId;
        $user = $user ? $user : Craft::$app->user;
        if ($user && $user->id) {
            $submission = PollAnswer::findOne(['pollId' => $pollId, 'userId' => $user->id]);
            return null !== $submission;
        }
        return in_array((int)$pollId, $this->getCookiePollIds(), true);
    }

    /**
     * @param Entry $poll the entry in the polls section
     * @param int $siteId the site from which the form was submitted
     * @param int $answerFieldId the fieldid (matrixblock) that contained the answers
     * @param array $answerUids uid's of the blocks in the field
     * @param array|null $answerTexts = ['answer_uid' => 'some comment', 'answer_uid2' => 'some other comment]
     * @return bool
     * @throws \craft\errors\InvalidFieldException
     */
    public function submit(Entry $poll, int $siteId, int $answerFieldId, array $answerUids, ?array $answerTexts = []): bool
    {
        $answerMatrix = $poll->getFieldValue($this->getConfigOption(self::CFG_FIELD_ANSWER_MATRIX_HANDLE));
        $answerTexts = is_array($answerTexts) ? $answerTexts : [];
        /* @var MatrixBlockQuery $answerMatrix */
        $answers = $answerMatrix->all();
        $answers = array_filter($answers, function ($a) use ($answerUids) {
            return in_array($a->uid, $answerUids, true);
        });
        /* @var $answers \craft\elements\MatrixBlock[] */
        if (count($answers) !== 1) {
            return false;
        }
        $this->addPollIdToCookie($poll->id);
        $user = Craft::$app->user;
        foreach ($answers as $answer) {
            $answerText = $answerTexts[$answer->uid] ?? null;
            $record = new PollAnswer([
                'pollId' => $poll->id,
                'siteId' => $siteId,
                'fieldId' => $answerFieldId,
                'answerId' => $answer->id,
                'userId' => $user ? $user->id : null,
                'answerText' => $answerText,
                'ip' => inet_pton(Craft::$app->request->getUserIP())
            ]);
            $record->save();
        }


        $poll->trigger(PollEvents::POLL_SUBMITTED, new PollSubmittedEvent([
            'poll' => $poll,
            'user' => $user,
            'answers' => $answers
        ]));

        return true;
    }

    /**
     * @param $pollOrPollId
     * @param null $status the status, defaults to all polls, also disabled.
     * @return Entry | null
     */
    public function getPoll($pollOrPollId, $status = null, $siteId = null)
    {
        if (!$pollOrPollId) {
            return null;
        }
        if ($pollOrPollId instanceof Entry) {
            return $this->isAPollEntry($pollOrPollId) ? $pollOrPollId : null;
        }
        $siteCookieName = 'Craft-' . Craft::$app->getSystemUid() . ':siteId';
        if(!$siteId) {
            $siteId = \Craft::$app->request->getRawCookies()->getValue($siteCookieName) ?: 1;
        }
        $q = Entry::find()
            ->siteId($siteId)
            ->section($this->getConfigOption(PollService::CFG_POLL_SECTION_HANDLE))
            ->id($pollOrPollId);

        return $q->one();
    }

    /**
     * Returns the section(s) that have polls
     * @return Section[]
     */
    public function getPollSections() : array
    {
        $sections = array_map(function(string $handle) {
            return Craft::$app->sections->getSectionByHandle($handle);
        }, [$this->getConfigOption(self::CFG_POLL_SECTION_HANDLE)]);
       return array_filter($sections);
    }

    /**
     * @param $pollOrPollId
     * @return MatrixBlock[]
     */
    public function getAnswers($pollOrPollId)
    {
        $poll = $this->getPoll($pollOrPollId);
        $matrix = $poll->getFieldValue($this->getConfigOption(self::CFG_FIELD_ANSWER_MATRIX_HANDLE));
        return $matrix->all();
    }

    /**
     * Gets answer labels
     *
     * @param array $pollOrPollIds
     * @return array = [232 => 'label 1', 443 => 'label 2']
     */
    public function getAnswerLabelsIndexedById(array $pollOrPollIds) {
        $labels = [];
        $polls = array_map([$this, 'getPoll'], $pollOrPollIds);
        foreach ($polls as $poll) {
            $answers = $this->getAnswers($poll);
            foreach ($answers as $answer) {
                $labels[$answer->id] = $answer->label ?? '(no label)';
            }
        }
        return $labels;
    }

    /**
     * Checks if a field is an answer martrix field.
     * Used to hook into the validation.
     *
     * @param $element
     * @return bool
     */
    public function isAnAnswerMatrix($element)
    {
        if (!$element instanceof Matrix) {
            return false;
        }
        // the handle must be one of the registered handles
        if ($element->handle !== $this->getConfigOption(self::CFG_FIELD_ANSWER_MATRIX_HANDLE)) {
            return false;
        }
        return true;
    }

    /**
     * Verify if something is a Poll entry
     *
     * @param $element
     * @return bool
     */
    public function isAPollEntry($element)
    {
        try {
            if (!$element instanceof Entry) {
                return false;
            }
            if ($element->section->handle !== $this->getConfigOption(self::CFG_POLL_SECTION_HANDLE)) {
                return false;
            }
            return true;
        } catch (InvalidConfigException $e) {
            // Sometimes for some reason craft passes entries with an invalid section during gc cleanup
            // as reported in: https://github.com/craftcms/cms/issues/7356
            // and https://github.com/24hoursmedia-craftcms/poll/issues/28
            // craft v3.5.17
            // the exception is caught here and the entry is considered 'no poll'.
            // this may result in orphaned answers
            return false;
        }
    }

    /**
     * When a matrix field is saved, check if the data is ok (propagation methods allowed etc).
     * An event handler of the plugin calls this method;
     * @param Matrix $matrix
     * @return bool
     * @see Poll::init()
     *
     */
    public function validateAnswerMatrixField(Matrix $matrix)
    {
        if (!$this->isAnAnswerMatrix($matrix)) {
            throw new \LogicException("The field to validate is not recognized as an answer matrix field!");
        }
        if ($matrix->propagationMethod === Matrix::PROPAGATION_METHOD_NONE) {
            $err = "You cannot set the propagation method to {$matrix->propagationMethod} for a Poll answers field";
            Craft::$app->session->setFlash('notice', $err);
            return false;
        }
        return true;
    }

    /**
     * Remove all answer submissions for a poll entry.
     * Called by an event handler when a poll entry is removed.
     * First check ::isAPollEntry before calling this method.
     *
     * @param $entry
     * @return int                          the number of records deleted
     * @see PollService::isAPollEntry()     to check if the entry is actually a poll
     * @see Poll::init()                    where the event handler is registered
     */
    public function removeAnswersForPoll($entry) : int {
        if (!isset($entry->id) || !$entry->id) {
            throw new \LogicException('No id set');
        }
        return PollAnswer::deleteAll(['pollId' => $entry->id]);
    }


}
