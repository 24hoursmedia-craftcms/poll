<?php
/**
 * poll plugin for Craft CMS 3.x
 *
 * poll plugin for craft 3.x
 *
 * @link      https://www.24hoursmedia.com
 * @copyright Copyright (c) 2020 24hoursmedia
 */

namespace twentyfourhoursmedia\poll\controllers;

use craft\elements\Entry;
use craft\web\Response;
use twentyfourhoursmedia\poll\Poll;

use Craft;
use craft\web\Controller;
use twentyfourhoursmedia\poll\services\PollService;
use yii\web\BadRequestHttpException;

/**
 * Answer Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    24hoursmedia
 * @package   Poll
 * @since     1.0.0
 */
class AnswerController extends Controller
{

    protected array | bool | int $allowAnonymous = ['submit'];

    /**
     * Message returned in json if user has already participated
     * @var string
     */
    protected $alreadyParticipatedMsg = 'Already participated';

    public function actionSubmit()
    {

        $this->requirePostRequest();

        // services
        $service = Poll::$plugin->pollService;

        // params
        $request = Craft::$app->getRequest();
        $pollId = trim($request->post($service->getConfigOption(PollService::CFG_FORM_POLLID_FIELDNAME)));
        $pollUid = trim($request->post($service->getConfigOption(PollService::CFG_FORM_POLLUID_FIELDNAME)));
        $siteId = trim($request->post($service->getConfigOption(PollService::CFG_FORM_SITEID_FIELDNAME)));
        $siteUid = trim($request->post($service->getConfigOption(PollService::CFG_FORM_SITEUID_FIELDNAME)));
        $answerFieldId = trim($request->post($service->getConfigOption(PollService::CFG_FORM_ANSWERFIELDID_FIELDNAME)));
        $answerFieldUid = trim($request->post($service->getConfigOption(PollService::CFG_FORM_ANSWERFIELDUID_FIELDNAME)));
        if ($pollId === '' || $pollUid === '' || $siteId === '' || $siteUid === '') {
            throw new BadRequestHttpException('Missing poll identifiers');
        }

        // get and validate the site
        $site = Craft::$app->sites->getSiteById($siteId);
        if (!$site || $site->uid !== $siteUid) {
            throw new BadRequestHttpException('Invalid site');
        }

        // get and validate the answer field that contained the answers (i.e. the matrix block)
        $answerField = Craft::$app->fields->getFieldById($answerFieldId);
        if (!$answerField || $answerField->uid !== $answerFieldUid) {
            throw new BadRequestHttpException('Invalid answer field');
        }



        // get and validate the poll (only enabled polls can be submitted)
        $poll = $service->getPoll($pollId, 'enabled', $siteId);
        if (!$poll || $poll->uid !== $pollUid) {
            throw new BadRequestHttpException('Poll disabled or invalid');
        }

        $success = false;
        $message = null;
        if (!$service->hasParticipated($poll)) {
            // get an answer uid
            $selectedAnswerUids =  array_filter(
                [$request->post($service->getConfigOption(PollService::CFG_FORM_POLLANSWER_FIELDNAME))[$poll->uid] ?? null]
            );

            // try to get the answer text for the submitted field
            $submittedAnswerTexts = $request->post($service->getConfigOption(PollService::CFG_FORM_POLLANSWERTEXT_FIELDNAME), []);
            $answerTexts = $submittedAnswerTexts[$pollUid] ?? [];

            $success = $service->submit($poll, (int)$siteId, (int)$answerFieldId, $selectedAnswerUids, $answerTexts);
        } else {
            $success = false;
            $message = $this->alreadyParticipatedMsg;
        }
        if (Craft::$app->request->getParam('redirect')) {
            return $this->redirectToPostedUrl();
        } else {
            return $this->asJson([
                'success' => $success,
                'message' => $message
            ]);
        }
    }
}
