<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 22/01/2020
 */

namespace twentyfourhoursmedia\poll\models;


use craft\base\Model;
use craft\web\User;

class PollResults extends Model
{

    public $count = 0;

    /**
     * @var ResultByAnswer[]
     */
    public $byAnswer = [];

    /**
     * An array of user id's that have participated in the poll
     * @var array
     */
    public $userIds = [];

    /**
     * List of users
     * @var User[]
     */
    public $users = [];


}