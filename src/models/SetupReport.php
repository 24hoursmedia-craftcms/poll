<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 23/01/2020
 */

namespace twentyfourhoursmedia\poll\models;

use Craft;
use craft\base\Model;


/**
 * Class SetupReport
 *
 * Contains info on the current setup state
 *
 * @package twentyfourhoursmedia\poll\models
 */
class SetupReport
{

    /**
     * @var array = [['level' => 'warning', 'description' => 'This is a warning', 'fix' => 'How to fix']
     */
    public $items = [];

    /**
     * @return array = [['level' => 'warning', 'description' => 'This is a warning', 'fix' => 'How to fix']
     */
    public function getItems(): array
    {
        return $this->items;
    }



    public function warn($description, $fix = null) {
        $this->items[] = ['level' => 'warning', 'description' => $description, 'fix' => $fix];
    }

    public function danger($description, $fix = null) {
        $this->items[] = ['level' => 'danger', 'description' => $description, 'fix' => $fix];
    }

    public function ok($description, $fix = null) {
        $this->items[] = ['level' => 'ok', 'description' => $description, 'fix' => $fix];
    }

}