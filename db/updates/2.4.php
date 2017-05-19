<?php
file_put_contents(CONNECTOR_DIR.'/db/version', $updateFile->getBasename('.php'));
