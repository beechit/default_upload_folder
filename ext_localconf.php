<?php

defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getDefaultUploadFolder'][] =
    \BeechIt\DefaultUploadFolder\Hooks\DefaultUploadFolder::class . '->getDefaultUploadFolder';
