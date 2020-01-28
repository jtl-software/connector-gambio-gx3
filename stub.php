<?php
if (file_exists(__DIR__.'/include.php')) {
	include(__DIR__.'/include.php');
}

Phar::mapPhar('connector.phar');

include_once 'phar://connector.phar/index.php';

__HALT_COMPILER();
