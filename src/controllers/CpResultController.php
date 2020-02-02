<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 01/02/2020
 */

namespace twentyfourhoursmedia\poll\controllers;
use Craft;

use craft\web\Controller;
use twentyfourhoursmedia\poll\Poll;
use yii\web\BadRequestHttpException;

class CpResultController extends Controller
{

    protected $allowAnonymous = false;

    public function actionResult()
    {
        $req = Craft::$app->getRequest();
        $id = $req->getQueryParam('id', null);
        if (!$id) {
            throw new BadRequestHttpException('Invalid id');
        }
        $service = Poll::$plugin->pollService;
        $poll = $service->getPoll($id);

        $simpleResults = $service->getResults($poll);
        return $this->renderTemplate('poll/_poll_results', [
            'poll' => $poll,
            'service' => $service,
            'simple_results' => $simpleResults
        ]);
    }

}