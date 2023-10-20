<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 01/02/2020
 */

namespace twentyfourhoursmedia\poll\elements;

use Craft;
use craft\elements\actions\Edit;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use twentyfourhoursmedia\commentswork\elements\db\CommentQuery;
use twentyfourhoursmedia\poll\elements\actions\CreateReport;
use twentyfourhoursmedia\poll\elements\db\PollQuery;

class Poll extends Entry
{

    protected static function defineActions(string $source = null): array
    {
        $actions = [];
        // Edit
        $actions[] = Craft::$app->getElements()->createAction(
            [
                'type' => CreateReport::class,
                //'label' => Craft::t('poll', 'Reports'),
            ]
        );
        return $actions;
    }

    /**
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('poll', 'Poll');
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::actionUrl('poll/cp-result/result?id='. $this->id);
    }

    public function getIsEditable(): bool
    {
        return false;
    }

    /**
     * Creates an [[ElementQueryInterface]] instance for query purpose.
     * @return CommentQuery.
     */
    public static function find(): EntryQuery
    {
        return new PollQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [];

        if ($context === 'index') {
            $sources = [
                [
                    'key' => '*',
                    'label' => Craft::t('poll', 'All polls'),
                    'criteria' => []
                ],
            ];
        }
        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('poll', 'Title')],
            'postDate' => ['label' => Craft::t('poll', 'Date')],
        ];
    }

}