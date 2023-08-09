<?php

declare(strict_types=1);

namespace BeechIt\DefaultUploadFolder\EventListener\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class DefaultUploadFolder
{
    public const DEFAULT_UPLOAD_FOLDERS = 'default_upload_folders.';
    public const DEFAULT_FOR_ALL_TABLES = 'defaultForAllTables';
    public function __invoke(AfterDefaultUploadFolderWasResolvedEvent $event): void
    {
        /** @var Folder $uploadFolder */
        $uploadFolder = $event->getUploadFolder() ?? null;
        $table = $event->getTable();
        $field = $event->getFieldName();
        $pid = $event->getPid();
        $pageTs = BackendUtility::getPagesTSconfig($pid);
        $userTsConfig = $GLOBALS['BE_USER']->getTsConfig();
        $subFolder = '';
        if ($table !== null && $field !== null) {
            $subFolder = $this->getDefaultUploadFolderForTableAndField($table, $field, $pageTs, $userTsConfig);
        }

        if (trim($subFolder) === '' && $field !== null) {
            $subFolder = $this->getDefaultUploadFolderForTable($table, $pageTs, $userTsConfig);
        }

        if (trim($subFolder) === '') {
            $subFolder = $this->getDefaultUploadFolderForAllTables($pageTs, $userTsConfig);
        }

        // Folder by combined identifier
        if (preg_match('/[0-9]+:/', $subFolder)) {
            try {
                $uploadFolder = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier(
                    $subFolder
                );
            } catch (FolderDoesNotExistException $e) {
                $uploadFolder = $this->createUploadFolder($subFolder);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $uploadFolder = null;
            }
        }

        if (trim($subFolder) && $uploadFolder instanceof Folder && $uploadFolder->hasFolder($subFolder)) {
            $uploadFolder = $uploadFolder->getSubfolder($subFolder);
        }

        $event->setUploadFolder($uploadFolder instanceof Folder ? $uploadFolder : null);
    }

    /**
     * Create upload folder
     *
     * @param $combinedFolderIdentifier
     * @return Folder|null
     */
    private function createUploadFolder($combinedFolderIdentifier): ?Folder
    {
        if (!str_contains($combinedFolderIdentifier, ':')) {
            return null;
        }
        $parts = explode(':', $combinedFolderIdentifier);
        $data = [
            'newfolder' => [
                0 => [
                    'data' => $parts[1],
                    'target' => $parts[0] . ':/',
                ],
            ],
        ];

        $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
        $fileProcessor->setActionPermissions();
        $fileProcessor->start($data);
        $fileProcessor->processData();
        return GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier(
            $combinedFolderIdentifier
        );
    }

    /**
     * Get default upload folder for table and field
     *
     * @param string $table
     * @param string $field
     * @param array $defaultPageTs
     * @param array $userTsConfig
     * @return string
     */
    protected function getDefaultUploadFolderForTableAndField(
        string $table,
        string $field,
        array $defaultPageTs,
        array $userTsConfig
    ): string {
        $subFolder = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'][$field] ?? '';
        $dateFormatConfig = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'][$field . '.'] ?? [];
        $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        if (empty($subFolder)) {
            $subFolder = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'][$field] ?? '';
            $dateFormatConfig = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'][$field . '.'] ?? [];
            $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        }
        return $subFolder;
    }

    /**
     * Get default upload folder for table
     *
     * @param string $table
     * @param array $defaultPageTs
     * @param array $userTsConfig
     * @return string
     */
    protected function getDefaultUploadFolderForTable(
        string $table,
        array $defaultPageTs,
        array $userTsConfig
    ): string {
        $subFolder = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][$table] ?? '';

        $dateFormatConfig = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'] ?? [];
        $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        if (empty($subFolder)) {
            $subFolder = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][$table] ?? '';
            $dateFormatConfig = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][$table . '.'] ?? [];
            $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        }
        return $subFolder;
    }

    /**
     * Get default upload folder for all tables
     *
     * @param array $defaultPageTs
     * @param array $userTsConfig
     * @return string
     */
    protected function getDefaultUploadFolderForAllTables(
        array $defaultPageTs,
        array $userTsConfig
    ): string {
        $subFolder = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][self::DEFAULT_FOR_ALL_TABLES] ?? '';

        $dateFormatConfig = $defaultPageTs[self::DEFAULT_UPLOAD_FOLDERS][self::DEFAULT_FOR_ALL_TABLES . '.'] ?? [];
        $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        if (empty($subFolder)) {
            $subFolder = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][self::DEFAULT_FOR_ALL_TABLES] ?? '';

            $dateFormatConfig = $userTsConfig[self::DEFAULT_UPLOAD_FOLDERS][self::DEFAULT_FOR_ALL_TABLES . '.'] ?? [];
            $subFolder = $this->checkAndConvertForDateFormat($subFolder, $dateFormatConfig);
        }
        return $subFolder;
    }

    /**
     * Check and convert for date format
     *
     * @param $subFolder
     * @param $dateFormatConfig
     * @return string $subFolder
     */
    protected function checkAndConvertForDateFormat($subFolder, $dateFormatConfig): string
    {
        if (trim($subFolder) === '') {
            return $subFolder;
        }
        if (!isset($dateFormatConfig['dateformat']) || (int)$dateFormatConfig['dateformat'] !== 1) {
            return $subFolder;
        }
        $strReplace = [
            '{Y}', '{y}',
            '{m}', '{n}',
            '{j}', '{d}',
            '{W}', '{w}',
        ];
        $replaceWith = [
            date('Y'), date('y'),
            date('m'), date('n'),
            date('j'), date('d'),
            date('W'), date('w'),
        ];
        return str_replace($strReplace, $replaceWith, $subFolder);
    }
}