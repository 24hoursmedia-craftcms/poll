<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 05/02/2020
 */

namespace twentyfourhoursmedia\poll\services;
use Craft;
use craft\base\Component;
use craft\elements\Entry;
use twentyfourhoursmedia\poll\models\PollResults;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\services\PollService;

/**
 * Class FacadeService
 *
 * The facade service is the public api that is exposed to twig.
 * Use this service to interact with the poll from outside as it's interface should be stable.
 *
 * @package twentyfourhoursmedia\poll\services
 */
class Facade extends Component
{

    /**
     * @var PollService
     */
    protected $service;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->service = Poll::$plugin->pollService;
    }

    /**
     * Returns a Poll entry, regardless of wether it is disabled or not
     *
     * @param $pollOrId
     * @return Entry|null
     */
    public function getPoll($pollOrId)
    {
        return $this->service->getPoll($pollOrId);
    }

    /**
     * Gets quick results for a poll
     *
     * @example {% set results = craft.poll.results(poll) %}
     *
     * @param Entry | int $pollOrId
     * @param array $opts = PollService::OPTS_GET_RESULTS
     * @return PollResults | null
     */
    public function getResults($pollOrId, $opts = []) {
        return $this->service->getResults($pollOrId, $opts);
    }

    /**
     * Checks if a user has participated already in a poll
     *
     * @example {% if craft.poll.hasParticipated(poll) %}..{% endif %}
     * @example {% if craft.poll.hasParticipated(poll, currentUser) %}..{% endif %}
     *
     * @param Entry | int $pollOrId
     * @param null $user
     * @return bool
     */
    public function hasParticipated($pollOrId, $user = null)
    {
        return $this->service->hasParticipated($pollOrId, $user);
    }

}