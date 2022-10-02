<?php
/**
 * Created by PhpStorm
 * User: eapbachman
 * Date: 22/01/2020
 */

namespace twentyfourhoursmedia\poll\services;

use Craft;
use craft\base\Component;
use craft\controllers\SectionsController;
use craft\elements\MatrixBlock;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use twentyfourhoursmedia\poll\models\SetupReport;
use twentyfourhoursmedia\poll\Poll;
use twentyfourhoursmedia\poll\services\traits\InstallServiceHelperTrait;


class InstallService extends Component
{

    use InstallServiceHelperTrait;


    /**
     * Ensures there is a 'select poll field'
     * @param $validateOnly
     * @param SetupReport $report
     * @return bool|\craft\base\FieldInterface|null
     * @throws \Throwable
     */
    private function ensureSelectPollField($validateOnly, SetupReport $report)
    {

        $config = Poll::$plugin->pollService->getConfig();
        $fieldHandle = $config[PollService::CFG_FIELD_SELECT_POLL_HANDLE];

        if ($validateOnly) {
            if (!$this->hasFieldTypeWithHandle($fieldHandle)) {
                $report->warn("There is no field type with handle {$fieldHandle}");
                return false;
            } else {
                $report->ok("There is a field type with handle {$fieldHandle}");
                return true;
            }
        }

        return $this->enforceFieldTypeWithHandle($fieldHandle, function () use ($config, $fieldHandle) {
            $fieldGroup = $this->enforceFieldGroupWithName($config[PollService::CFG_FIELD_GROUP_NAME]);
            $sectionHandle = $config[PollService::CFG_POLL_SECTION_HANDLE];
            $section = Craft::$app->sections->getSectionByHandle($sectionHandle);

            $field = new Entries();
            $field->groupId = $fieldGroup->id;
            $field->handle = $fieldHandle;
            $field->name = 'Select poll';
            $field->allowLimit = true;
            $field->maxRelations = 1;
            $field->allowMultipleSources = false;
            $field->sources = ['section:' . $section->uid];
            return $field;
        });

    }


    /**
     * Checks if a section has the answers matrix in it's entry type
     * @param Section $section
     * @param bool $validateOnly
     * @param SetupReport $report
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\EntryTypeNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    private function ensureSectionHasAnswersMatrix(Section $section, bool $validateOnly, SetupReport $report)
    {
        $config = Poll::$plugin->pollService->getConfig();
        $sectionHandle = $config[PollService::CFG_POLL_SECTION_HANDLE];
        $fieldHandle = $config[PollService::CFG_FIELD_ANSWER_MATRIX_HANDLE];
        $types = $section->entryTypes;
        $type = $types[0] ?? null;


        if (!$type) {
            $report->warn('No entry type for section ' . $sectionHandle . ' found.');
            if ($validateOnly) {
                return false;
            }
        }

        $matrixField = $type->getFieldLayout()->getFieldByHandle($fieldHandle);
        if (!$matrixField) {
            if ($validateOnly) {
                $report->warn("Entry type in section {$sectionHandle} does not contain matrix field with handle {$fieldHandle}");
                return false;
            } else {

                $matrix = Craft::$app->fields->getFieldByHandle($fieldHandle);
                $tabs = $type->getFieldLayout()->getTabs();
                $tab = $tabs[0] ?? null;
                if (!$tab) {
                    $tab = new FieldLayoutTab();
                    $tab->name = 'Poll';
                    $type->getFieldLayout()->setTabs([$tab]);
                }

                $newElement = [
                    'type' => CustomField::class,
                    'fieldUid' => $matrix->uid,
                    'required' => false,
                ];
                $tab->setElements(array_merge($tab->getElements(), ['new1' => $newElement]));

                $tabs[0] = $tab;
                $type->getFieldLayout()->setTabs($tabs);
                $success = Craft::$app->fields->saveLayout($type->getFieldLayout());
                if ($success) {
                    $report->ok("Created in Section {$sectionHandle}: entry type with handle {$fieldHandle}");
                } else {
                    $report->danger("FAILED: Created in Section {$sectionHandle}: entry type with handle {$fieldHandle}");
                }
                return $success;
            }
        }
        return true;


    }

    /**
     * Ensure a polls section is present
     *
     * @param bool $validateOnly
     * @param SetupReport $report
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\SectionNotFoundException
     * @see SectionsController::actionSaveSection()
     */
    private function ensureSection(bool $validateOnly, SetupReport $report): bool
    {
        $config = Poll::$plugin->pollService->getConfig();
        $sectionHandle = $config[PollService::CFG_POLL_SECTION_HANDLE];

        $hasSection = $this->hasSectionWithHandle($sectionHandle);

        if ($validateOnly) {
            if ($hasSection) {
                $report->ok(
                    sprintf('There is a section in Craft with handle %s', $sectionHandle)
                );

                // additional check
                $section = Craft::$app->sections->getSectionByHandle($sectionHandle);
                $hasMatrix = $this->ensureSectionHasAnswersMatrix($section, $validateOnly, $report) ? true : false;
                if ($hasMatrix) {
                    $report->ok(
                        sprintf("The entry type in section $sectionHandle contains the answers matrix", $sectionHandle)
                    );
                } else {
                    $report->warn(
                        sprintf("The entry type in section $sectionHandle does not contain the answers matrix", $sectionHandle)
                    );
                }

                return $hasMatrix;
            } else {
                $report->warn(
                    sprintf('There is no section in Craft with handle %s', $sectionHandle)
                );
                return false;
            }
        }

        $section = Craft::$app->sections->getSectionByHandle($sectionHandle);
        if ($section) {
            return $this->ensureSectionHasAnswersMatrix($section, $validateOnly, $report);
        }


        // create a new poll section
        $section = new Section([]);
        $section->handle = $sectionHandle;
        $section->name = 'Polls';
        $section->type = Section::TYPE_CHANNEL;
        $section->enableVersioning = false;
        $section->propagationMethod = Section::PROPAGATION_METHOD_ALL;

        $allSiteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $settings = new Section_SiteSettings();
            $settings->siteId = $site->id;
            $settings->uriFormat = null;
            $settings->enabledByDefault = true;
            $settings->hasUrls = false;
            $allSiteSettings[$site->id] = $settings;
        }
        $section->setSiteSettings($allSiteSettings);
        $success = Craft::$app->sections->saveSection($section, true);
        if (!$success) {

            $report->danger("Couldn't save section {$sectionHandle}");
            return false;
        }

        $this->ensureSectionHasAnswersMatrix($section, $validateOnly, $report);
        $report->ok('Section ' . $sectionHandle . ' created.');
        return true;
    }


    /**
     * Ensures a matrix field for containing answers is present
     *
     * @param bool $validateOnly
     * @param SetupReport $report
     * @return bool|\craft\base\FieldInterface|null
     * @throws \Throwable
     */
    private function ensureMatrix(bool $validateOnly, SetupReport $report)
    {
        $config = Poll::$plugin->pollService->getConfig();
        $fieldHandle = $config[PollService::CFG_FIELD_ANSWER_MATRIX_HANDLE];

        if ($validateOnly) {
            if ($this->hasFieldTypeWithHandle($fieldHandle)) {
                $report->ok(
                    sprintf('There is a matrix field with handle %s', $fieldHandle)
                );
                return true;
            } else {
                $report->warn(
                    sprintf('There is no matrix field in Craft with handle %s', $fieldHandle),
                    'Run setup to initialize the matrix field'
                );
                return false;
            }
        }


        $heap = [
            'field_layout' => null,
        ];

        return $this->enforceFieldTypeWithHandle(
            $fieldHandle,
            function () use ($config, $fieldHandle, &$heap) {
                $fieldLayout = new FieldLayout([]);
                $fieldLayout->type = MatrixBlock::class;

                $tab = new FieldLayoutTab();
                $tab->name = 'Content';
                $tab->sortOrder = 1;
                $fieldLayout->setTabs([$tab]);

                $heap['field_layout'] = $fieldLayout;

                $matrix = new Matrix();
                $matrix->handle = $fieldHandle;
                $matrix->name = 'Poll answers';
                $matrix->groupId = $this->enforceFieldGroupWithName($config[PollService::CFG_FIELD_GROUP_NAME])->id;
                $matrix->propagationMethod = Matrix::PROPAGATION_METHOD_ALL;
                $matrix->setBlockTypes([
                    'new1' => [
                        'name' => 'Answer',
                        'handle' => $config[PollService::CFG_MATRIXBLOCK_ANSWER_HANDLE],
                        'fields' => [
                            'new1' => [
                                'type' => PlainText::class,
                                'name' => 'Label',
                                'handle' => 'label',
                            ]
                        ]
                    ]
                ]);
                return $matrix;
            }, function ($matrix) use (&$heap) {
        });
    }

    /**
     * @param bool $validateOnly
     * @param SetupReport $setupReport
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\SectionNotFoundException
     */
    private function apply(bool $validateOnly, SetupReport $setupReport)
    {
        $success = true;
        $success = ($success || $validateOnly) && $this->ensureMatrix($validateOnly, $setupReport);
        $success = ($success || $validateOnly) && $this->ensureSection($validateOnly, $setupReport);
        $success = ($success || $validateOnly) && $this->ensureSelectPollField($validateOnly, $setupReport);
        return $success;
    }

    /**
     * Checks validation
     * @param SetupReport|null $setupReport
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\SectionNotFoundException
     */
    public function check(SetupReport $setupReport = null)
    {
        if (!$setupReport) {
            $setupReport = new SetupReport();
        }
        $success = $this->apply(true, $setupReport);
        return $success;
    }

    /**
     * Applies setup
     *
     * @param SetupReport|null $setupReport
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\MissingComponentException
     * @throws \craft\errors\SectionNotFoundException
     */
    public function setup(SetupReport $setupReport = null)
    {
        if (!$setupReport) {
            $setupReport = new SetupReport();
        }

        $success = $this->apply(false, $setupReport);
        //if ($success) {
        //    Craft::$app->getSession()->setNotice(Craft::t('app', 'Poll installation seems ok.'));
        //} else {
        //    Craft::$app->getSession()->setNotice(Craft::t('app', 'Poll installation failed.'));
        //}

        return $success;
    }


}