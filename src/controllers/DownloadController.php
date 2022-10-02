<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 01/02/2020
 */

namespace twentyfourhoursmedia\poll\controllers;

use Craft;
use craft\web\Controller;
use twentyfourhoursmedia\poll\helper\CsvHelper;
use twentyfourhoursmedia\poll\Poll;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class DownloadController extends Controller
{

    protected array | bool | int $allowAnonymous = false;

    /**
     * Gets a poll entry from the request
     * @return \craft\elements\Entry|null
     * @throws BadRequestHttpException
     */
    private function getPoll()
    {
        $poll = Poll::$plugin->pollService->getPoll(
            Craft::$app->getRequest()->getQueryParam('id')
        );
        if (!$poll) {
            throw new BadRequestHttpException('Not a poll');
        }
        return $poll;
    }

    /**
     * Downloads poll data in CSV format
     * action route: poll/data/poll-data
     */
    public function actionPollData(): Response
    {
        $this->requireCpRequest();
        $this->requirePermission('accessplugin-poll');


        $polls = [$this->getPoll()];
        $data = Poll::$plugin->resultService->getData($polls);

        $fh = tmpfile();

        // append answer labels
        $labels = Poll::$plugin->pollService->getAnswerLabelsIndexedById($polls);
        foreach ($data as $k => $row) {
            $data[$k]['answer_label'] = $labels[$row['answer_id']] ?? '(no label)';
        }

        $columns = CsvHelper::getColumns($data);
        // order columns, place answer_text at end
        $idx = array_search('answer_text', $columns, true);
        if (false !== $idx) {
            unset($columns[$idx]);
            $columns[] = 'answer_text';
        }

        CsvHelper::createCsv($fh, $columns, $data);
        fseek($fh, 0);

        $attachmentName = 'polldata-' . date('YmdHis') . '.csv';

        $response = new Response();
        $response->sendStreamAsFile($fh, $attachmentName);


        return $response;
    }

}