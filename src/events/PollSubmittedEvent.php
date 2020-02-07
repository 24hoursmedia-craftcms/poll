<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 07/02/2020
 */
namespace twentyfourhoursmedia\poll\events;
use craft\elements\Entry;
use craft\elements\User;
use craft\records\MatrixBlock;
use yii\base\Event;

/**
 * Class PollSubmittedEvent
 *
 * Triggered after a vote has been made on a poll
 *
 * @package twentyfourhoursmedia\poll\events
 */
class PollSubmittedEvent extends Event
{

    const NAME = 'POLL_SUBMIT_EVENT';

    /**
     * @var Entry
     */
    public $poll;

    /**
     * @var User | null
     */
    public $user;

    /**
     * @var MatrixBlock[]
     */
    public $answers = [];

}