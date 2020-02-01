<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 01/02/2020
 */

namespace twentyfourhoursmedia\poll\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\records\User;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\records\PollAnswer;
use yii\db\conditions\InCondition;

class ResultService extends Component
{
    /**
     * @var PollService
     */
    private $pollService;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->pollService = Poll::$plugin->pollService;
    }

    private function getValidPollIds(array $pollsOrIds) {
        $ids = array_map(function($pollOrId) {
            $poll = $this->pollService->getPoll($pollOrId);
            return $poll->id ?? null;
        }, $pollsOrIds);
        return array_filter($ids);
    }

    /**
     * Gets the raw data for one or more polls
     * @param array $pollsOrIds
     * @return array
     */
    public function getData(array $pollsOrIds)
    {
        $ids = $this->getValidPollIds($pollsOrIds);
        if ([] === $ids) {
            return [];
        }
        $stmt = (new Query())
            ->select('
            a.dateCreated AS date,
            a.pollId AS poll_id, 
            a.answerId as answer_id,
            a.userId as user_id,
            u.email as user_email,
            u.userName as username
            '
            )
            ->from(PollAnswer::tableName() . ' a')
            ->leftJoin(User::tableName() . ' u', 'a.userId=u.id')
            ->where(
                new InCondition('a.pollId', 'IN', $ids)
            )
            ->addOrderBy('a.id DESC')
           ;

        return $stmt->all();
    }

}