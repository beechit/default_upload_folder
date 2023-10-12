<?php

declare(strict_types=1);

namespace BeechIt\DefaultUploadFolder\Hooks;

// All code (c) Beech Applications B.V. all rights reserved

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;

class DefaultUploadFolder
{
    const DEFAULT_UPLOAD_FOLDERS = 'default_upload_folders.';
    const DEFAULT_FOR_ALL_TABLES = 'defaultForAllTables';

    /**
     * Get default upload folder for table
     * If none is found for current table defaultForAllTables is used.
     *
     * @param array $params
     * @param BackendUserAuthentication $backendUserAuthentication
     * @return Folder|null
     */
    public function getDefaultUploadFolder(array $params, BackendUserAuthentication $backendUserAuthentication): ?Folder
    {
        $rteParameters = $_GET['P'] ?? [];

        /** @var Folder $uploadFolder */
        $uploadFolder = $params['uploadFolder'];
        $table = $params['table'] ?? $rteParameters['table'] ?? null;
        $field = $params['field'] ?? $rteParameters['fieldName'] ?? null;
        $pid = $params['pid'] ?? $rteParameters['pid'] ?? 0;
        $pageTs = BackendUtility::getPagesTSconfig($pid);
        $userTsConfig = $backendUserAuthentication->getTSConfig();
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

        return ($uploadFolder instanceof Folder) ? $uploadFolder : null;
    }

    /**
     * Create upload folder
     *
     * @param $combinedFolderIdentifier
     * @return Folder|null
     */
    private function createUploadFolder($combinedFolderIdentifier): ?Folder
    {
        if (strpos($combinedFolderIdentifier, ':') === false) {
            return null;
        }
        $parts = explode(':', $combinedFolderIdentifier);
        // Split $combinedFolderIdentifier into target and data(folders to be created) due possible permissions mismatch.
        // When an user has access to a subdir by filemount but not access to the full storage, the root target (/) is checked for permission.
        // Therefore, an exception will be thrown. Checking and specifying the target more precise this will be avoid.
        $dirs = explode('/', trim($parts[1], '/'));
        $lastItem = array_pop($dirs);
        $nonExistingDirs = [];
        while ($lastItem !== null) {
            $nonExistingDirs = [$lastItem, ...$nonExistingDirs];
            try {
                GeneralUtility::makeInstance(ResourceFactory::class)
                    ->getFolderObjectFromCombinedIdentifier(
                        $parts[0] . ':/' . implode('/', $dirs)
                    );
                break;
            } catch (FolderDoesNotExistException $folderDoesNotExistException) {
            }
            $lastItem = array_pop($dirs);
        }
        $data = [
            'newfolder' => [
                0 => [
                    'data' => implode('/', $nonExistingDirs),
                    'target' => $parts[0] . ':/' . implode('/', $dirs),
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
            '{Y}',
            '{y}',
            '{m}',
            '{n}',
            '{j}',
            '{d}',
            '{W}',
            '{w}',
        ];
        $replaceWith = [
            date('Y'),
            date('y'),
            date('m'),
            date('n'),
            date('j'),
            date('d'),
            date('W'),
            date('w'),
        ];
        return str_replace($strReplace, $replaceWith, $subFolder);
    }
}
