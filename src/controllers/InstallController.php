<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 22/01/2020
 */

namespace twentyfourhoursmedia\poll\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Request;
use twentyfourhoursmedia\poll\Poll;
use yii\web\BadRequestHttpException;

class InstallController extends Controller
{

    protected $allowAnonymous = false;

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/poll/install
     *
     * @return mixed
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \craft\errors\SectionNotFoundException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionSetup()
    {
        $this->requireCpRequest();
        $this->requireAdmin(true);
        if (Craft::$app->getRequest()->getMethod() !== 'POST') {
            throw new BadRequestHttpException('Post method required');
        }

        $service  = Poll::getInstance()->installService;
        $service->setup();
        return $this->redirectToPostedUrl();
    }

}