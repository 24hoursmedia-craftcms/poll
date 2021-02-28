<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\records;

use craft\db\ActiveRecord;
use yii\db\Schema;


/**
 * PollAnswer Record
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class PollAnswer extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%poll_pollanswer}}';
    }


    /**
     * Wether to use the custom insert/update implementation
     * @return bool
     */
    private function useCustomImplementation() {
        return true;
    }

    /**
     * Replaces static::getDb()->schema->insert(static::tableName(), $values)
     * that contains a createCommand statement without the option to exclude audit columns.
     * We want audit columns (dateUpdated, uid) excluded.
     *
     * @param $table
     * @param $values
     * @return array|bool
     * @throws \yii\db\Exception
     * @see Schema::insert()
     */
    private function customDbSchemaInsert($table, $columns) {
        $context = static::getDb()->schema;

        // what really modified is the false flag.
        $command = $context->db->createCommand()->insert($table, $columns, false);
        if (!$command->execute()) {
            return false;
        }
        $tableSchema = $context->getTableSchema($table);
        $result = [];
        foreach ($tableSchema->primaryKey as $name) {
            if ($tableSchema->columns[$name]->autoIncrement) {
                $result[$name] = $context->getLastInsertID($tableSchema->sequenceName);
                break;
            }

            $result[$name] = $columns[$name] ?? $tableSchema->columns[$name]->defaultValue;
        }

        return $result;
    }

    /**
     * Overrides the default insert method with a custom method that
     * excludes 'audit columns' such as uid.
     * if  self::useCustomImplementation returns false the default method is used.
     *
     * Note that if you want to update columns one might want to override the updateInternal method too.
     * @see ActiveRecord::updateInternal()
     *
     * @inheritDoc
     */
    protected function insertInternal($attributes = null)
    {
        if (!$this->useCustomImplementation()) {
            return parent::insertInternal($attributes);
        }

        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);

        $primaryKeys = $this->customDbSchemaInsert(static::tableName(), $values);
        if ($primaryKeys === false) {
            return false;
        }

        foreach ($primaryKeys as $name => $value) {
            $id = static::getTableSchema()->columns[$name]->phpTypecast($value);
            $this->setAttribute($name, $id);
            $values[$name] = $id;
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }


}
