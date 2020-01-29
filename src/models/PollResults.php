<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 22/01/2020
 */

namespace twentyfourhoursmedia\poll\models;


use craft\base\Model;

class PollResults extends Model
{

    public $count = 0;

    /**
     * @var array = [ ['answer' => MatrixBlock, 'total' => 3], ['answer' => MatrixBlock, 'count' => 6]]
     */
    public $byAnswer = [];


}