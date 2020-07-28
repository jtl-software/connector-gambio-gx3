<?php

$urlFolders = str_replace("/jtlconnector/install/loggingConfig.php", "", $_SERVER['REQUEST_URI']);
$projectdir = $_SERVER['DOCUMENT_ROOT'] . $urlFolders;
$logFolder = $projectdir . '/jtlconnector/logs/';
$downloadFolder = $projectdir . '/jtlconnector/install/';

if (isset($_REQUEST['download'])) {
    if (file_exists($downloadFolder . 'logs.zip')) {
        unlink($downloadFolder . 'logs.zip');
    }
    
    $zip = new \ZipArchive();
    $zip->open($downloadFolder . 'logs.zip', \ZipArchive::CREATE);
    
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            $zip->addFile($logFolder . $file, $file);
        }
    }
    $zip->close();
} elseif (isset($_REQUEST['clear'])) {
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            unlink($logFolder . $file);
        }
    }
}
