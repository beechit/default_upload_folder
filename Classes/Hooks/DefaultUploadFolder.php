<?php
declare(strict_types=1);
namespace BeechIt\DefaultUploadFolder\Hooks;
/*
 * All code (c) Beech Applications B.V. all rights reserved
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    public function getDefaultUploadFolder($params, BackendUserAuthentication $backendUserAuthentication):Folder
    {
        /** @var Folder $uploadFolder */
        $uploadFolder = $params['uploadFolder'];
        $table = $params['table'];
        $field = $params['field'];
        $pageTs = BackendUtility::getPagesTSconfig($params['pid']);

        $subFolder = $pageTs['default_upload_folders.'][$table . '.'][$field] ?? $pageTs['default_upload_folders.'][$table] ?? '';
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

        if ($subFolder && $uploadFolder instanceof Folder && $uploadFolder->hasFolder($subFolder)
        ) {
            $uploadFolder = $uploadFolder->getSubfolder($subFolder);
        }

        return $uploadFolder;
    }
}
