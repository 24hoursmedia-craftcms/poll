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
use craft\elements\User;
use twentyfourhoursmedia\poll\models\PollResults;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\records\PollAnswer;
use twentyfourhoursmedia\poll\models\ResultByAnswer;
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

    // default options that can be passed to get results
    const OPTS_GET_RESULTS = [
        // set to true to include the users that have participated, for the total poll and by each answer
        'with_users' => false,
        // set to true to return only user ids. saves memory
        'user_id_only' => false,
        // limit on the amount of users returned, to save memory
        'limit_users' => 1000
    ];

    /**
     * Generate simple results
     *
     * @param $pollOrPollId
     * @param array $opts = self::OPTS_GET_RESULTS
     * @return PollResults | null
     */
    public function getResults($pollOrPollId, $opts = [])
    {
        $opts+= self::OPTS_GET_RESULTS;
        $poll = $this->pollService->getPoll($pollOrPollId, null);
        if (!$poll) {
            return null;
        }

        $model = new PollResults();
        $model->count = (int)PollAnswer::find()->andWhere('pollId=:pollId', ['pollId' => $poll->id])->count();

        $byAnswers = (new Query())
            ->select('answerId, count(id) as total')
            ->from(PollAnswer::tableName())
            ->where('pollId=:pollId')->addParams(['pollId' => $poll->id])
            ->addGroupBy(['answerId'])
            ->all();
        $indexedAnswers = array_reduce($byAnswers, static function ($carry, $item) {
            $carry[$item['answerId']] = (int)$item['total'];
            return $carry;
        }, []);

        foreach ($this->pollService->getAnswers($pollOrPollId) as $answer) {
            $count = $indexedAnswers[$answer->id] ?? 0;
            $resultByAnswer = new ResultByAnswer();
            $resultByAnswer->count = $count;
            $resultByAnswer->percent = $model->count > 0 ? 100 * $count / $model->count : null;
            $resultByAnswer->answer = $answer;
            $model->byAnswer[] = $resultByAnswer;
        }

        // enrich with users?
        if ($opts['with_users']) {
            // array with key => user over all answers..
            $carryUsers = [];

            // add users by answer
            foreach ($model->byAnswer as $byAnswer) {
                $records = (new Query())
                    ->select('userId')
                    ->from(PollAnswer::tableName())
                    ->where('pollId=:pollId')
                    ->andWhere('answerId=:answerId')
                    ->andWhere('userId IS NOT NULL')
                    ->addOrderBy('dateCreated DESC')
                    ->indexBy('userId')
                    ->limit($opts['limit_users'])
                    ->addParams(['pollId' => $poll->id, 'answerId' => $byAnswer->answer->id])
                    ->all();
                $userIds = array_keys($records);
                $byAnswer->userIds = $userIds;
                if (!$opts['user_id_only']) {
                    $users = $this->getUsersFromIds($userIds);
                    $carryUsers+= $users;
                    $byAnswer->users = array_values($users);
                }
            }

            // add all users
            // we take the users from the carry to save memory
            // this is applicable only if the total users to return <= number of users by answer
            $records = (new Query())
                ->select('userId')
                ->from(PollAnswer::tableName())
                ->where('pollId=:pollId')
                ->andWhere(new InCondition('userId', 'IN', array_keys($carryUsers)))
                ->addOrderBy('dateCreated DESC')
                ->indexBy('userId')
                ->limit($opts['limit_users'])
                ->addParams(['pollId' => $poll->id])
                ->all();
            $userIds = array_keys($records);
            $model->userIds = $userIds;
            if (!$opts['user_id_only']) {
                $model->users = array_values(
                    array_intersect_key($carryUsers, array_flip($userIds))
                );
            }
        }

        return $model;
    }

    /**
     * Returns users from a list of ids in the same order as the ids were supplied
     * By contract, the keys are the user ids
     *
     * @param $userIds
     * @return User[] = ['id' => User::class, 'id2' => User::class]
     */
    private function getUsersFromIds($userIds) {
        $users = User::find()->id($userIds)->anyStatus()->indexBy('id')->all();
        return array_intersect_key($users, array_flip($userIds));
    }
}