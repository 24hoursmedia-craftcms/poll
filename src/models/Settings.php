<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\models;

use twentyfourhoursmedia\poll\Poll;

use Craft;
use craft\base\Model;

/**
 * Poll Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class Settings extends Model
{

    // settings for the plugin.
    // you can override these by placing a file 'poll.php' inside the /config folder.
    // if any value is empty it reverts to default.
    public $sectionHandle = '';
    public $selectPollFieldHandle = '';
    public $answerMatrixFieldHandle = '';
    public $matrixBlockAnswerHandle = '';


    /**
     * Blocks removal of the plugin as a safety measure
     *
     * @var bool
     */
    public $blockPluginRemoval = true;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['blockPluginRemoval', 'boolean']
        ];
    }
}
