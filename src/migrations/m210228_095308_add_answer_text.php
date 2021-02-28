<?php

namespace twentyfourhoursmedia\poll\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210228_095308_add_answer_text migration.
 */
class m210228_095308_add_answer_text extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%poll_pollanswer}}',
            'answerText',
            $this->mediumText()->null()
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%poll_pollanswer}}', 'answerText');
    }
}
