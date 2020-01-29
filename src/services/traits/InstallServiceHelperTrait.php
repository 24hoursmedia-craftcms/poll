<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 22/01/2020
 */

namespace twentyfourhoursmedia\poll\services\traits;
use Craft;
use craft\models\FieldGroup;

trait InstallServiceHelperTrait
{


    /**
     * Gets a field group to install fields in (defaults to first)
     *
     * @param string|null $name
     * @return FieldGroup | null
     * @throws \Throwable
     */
    protected function enforceFieldGroupWithName(string $name = null)
    {

        $groups = Craft::$app->fields->getAllGroups();
        foreach ($groups as $group) {
            if (strtolower($group->name) === strtolower($name)) {
                return $group;
            }
        }
        $group = new FieldGroup();
        $group->name = $name;
        $success = Craft::$app->fields->saveGroup($group, true);
        return $success ? $group : false;
    }

    /**
     * @param string $handle
     * @return bool
     */
    protected function hasFieldTypeWithHandle(string $handle): bool
    {
        $field = Craft::$app->fields->getFieldByHandle($handle);
        return $field ? true : false;
    }

    /**
     * @param string $handle
     * @return bool
     */
    protected function hasSectionWithHandle(string $handle): bool
    {
        $section = Craft::$app->sections->getSectionByHandle($handle);
        return $section ? true : false;
    }

    /**
     * Makes sure a field with a handle exists, if not retrieves the field from the callback and create it
     *
     * @param $handle
     * @param callable $callback
     * @return bool|\craft\base\FieldInterface|null
     * @throws \Throwable
     */
    protected function enforceFieldTypeWithHandle($handle, $callback) {
        $field = Craft::$app->fields->getFieldByHandle($handle);
        if (!$field) {
            $field = $callback();
            if (!Craft::$app->fields->saveField($field, true)) {
                return false;
            }
        }
        return $field;
    }

}