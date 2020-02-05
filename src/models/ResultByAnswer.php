<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 05/02/2020
 */

namespace twentyfourhoursmedia\poll\models;


use craft\elements\MatrixBlock;
use craft\web\User;

class ResultByAnswer
{

    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var null | float
     */
    public $percent = null;

    /**
     * @var MatrixBlock
     */
    public $answer;

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