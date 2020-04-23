<?php

namespace BeechIt\DefaultUploadFolder\Hooks;
/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 06-04-2016
 * All code (c) Beech Applications B.V. all rights reserved
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class DefaultUploadFolder
 */
class DefaultUploadFolder
{
    /**
     * Get default upload folder
     *
     * @param array $params
     * @param BackendUserAuthentication $backendUserAuthentication
     * @return Folder
     */
    public function getDefaultUploadFolder($params, BackendUserAuthentication $backendUserAuthentication)
    {
        /** @var Folder $uploadFolder */
        $uploadFolder = $params['uploadFolder'];
        $table = $params['table'];
        $field = $params['field'];
        $pageTs = BackendUtility::getPagesTSconfig($params['pid']);
        $userTsConfig = $backendUserAuthentication->getTSConfig();
        $subFolder = $this->getDefaultUploadFolderForTableAndField($table, $field, $pageTs, $userTsConfig);
        if (trim($subFolder) === '') {
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
                // todo: try to create the folder
            }
        }
        if (
            $uploadFolder instanceof Folder
            &&
            $subFolder !== ''
            &&
            $uploadFolder->hasFolder($subFolder)
        ) {
            $uploadFolder = $uploadFolder->getSubfolder($subFolder);
        }
        return $uploadFolder;
    }

    public function getDefaultUploadFolderForTableAndField(
        $table,
        $field,
        array $defaultPageTs,
        array $userTsConfig
    )
    {
        $subFolder = $defaultPageTs['default_upload_folders.'][$table][$field] ?? '';
        if (empty($subFolder)) {
            $subFolder = $userTsConfig['default_upload_folders.'][$table][$field] ?? '';
        }
        return $subFolder;
    }

    protected function getDefaultUploadFolderForTable(
        $table,
        array $defaultPageTs,
        array $userTsConfig
    )
    {
        $subFolder = $defaultPageTs['default_upload_folders.'][$table] ?? '';
        if (empty($subFolder)) {
            $subFolder = $userTsConfig['default_upload_folders.'][$table] ?? '';
        }
        return $subFolder;
    }

    protected function getDefaultUploadFolderForAllTables(
        array $defaultPageTs,
        array $userTsConfig
    )
    {
        $subFolder = $defaultPageTs['default_upload_folders.']['defaultForAllTables'] ?? '';
        if (empty($subFolder)) {
            $subFolder = $userTsConfig['default_upload_folders.']['defaultForAllTables'] ?? '';
        }
        return $subFolder;
    }
}