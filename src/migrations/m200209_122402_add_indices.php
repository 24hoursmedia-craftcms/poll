<?php

namespace twentyfourhoursmedia\poll\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200209_122402_add_indices migration.
 */
class m200209_122402_add_indices extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->createIndex(
            $this->db->getIndexName('{{%poll_pollanswer}}',['pollId', 'userId'],false),
            '{{%poll_pollanswer}}', ['userId', 'pollId'], false
        );
        $this->createIndex(
            $this->db->getIndexName('{{%poll_pollanswer}}',['dateCreated'],false),
            '{{%poll_pollanswer}}', ['dateCreated'], false
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex(
            $this->db->getIndexName('{{%poll_pollanswer}}',['dateCreated'],false),
            '{{%poll_pollanswer}}', false
        );

        $this->dropIndex(
            $this->db->getIndexName('{{%poll_pollanswer}}',['pollId', 'userId'],false),
            '{{%poll_pollanswer}}', false
        );

    }
}
