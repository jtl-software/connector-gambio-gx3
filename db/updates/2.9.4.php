<?php

$db->query("ALTER TABLE jtl_connector_link_product MODIFY endpoint_id VARCHAR(255)");

file_put_contents(CONNECTOR_DIR.'/db/version', $updateFile->getBasename('.php'));