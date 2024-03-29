<?php

use jtl\Connector\Linker\IdentityLinker;

$types = array(
    IdentityLinker::TYPE_CATEGORY => 'category',
    IdentityLinker::TYPE_CUSTOMER => 'customer',
    IdentityLinker::TYPE_CUSTOMER_ORDER => 'customer_order',
    IdentityLinker::TYPE_DELIVERY_NOTE => 'delivery_note',
    IdentityLinker::TYPE_IMAGE => 'image',
    IdentityLinker::TYPE_MANUFACTURER => 'manufacturer',
    IdentityLinker::TYPE_PRODUCT => 'product',
    IdentityLinker::TYPE_PAYMENT => 'payment',
    IdentityLinker::TYPE_CROSSSELLING => 'crossselling',
    IdentityLinker::TYPE_CROSSSELLING_GROUP => 'crossselling_group'
);

$queryInt = 'CREATE TABLE IF NOT EXISTS %s (
  endpoint_id INT(10) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

$queryChar = 'CREATE TABLE IF NOT EXISTS %s (
  endpoint_id varchar(255) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

foreach ($types as $id => $name) {
    if (in_array($id, [IdentityLinker::TYPE_IMAGE, IdentityLinker::TYPE_PRODUCT])) {
        $db->query(sprintf($queryChar, 'jtl_connector_link_' . $name));
    } else {
        $db->query(sprintf($queryInt, 'jtl_connector_link_' . $name));
    }
}

$check = $db->query('SHOW TABLES LIKE "jtl_connector_link"');

if (count($check) == 1) {
    $existingTypes = $db->query('SELECT type FROM jtl_connector_link GROUP BY type');

    foreach ($existingTypes as $existingType) {
        $typeId = (int)$existingType['type'];
        $tableName = 'jtl_connector_link_' . $types[$typeId];

        $db->query("INSERT INTO {$tableName} (host_id, endpoint_id) 
      SELECT hostId, endpointId FROM jtl_connector_link WHERE type = {$typeId}
    ");
    }

    $db->query("RENAME TABLE jtl_connector_link TO jtl_connector_link_backup");

    $db->query("ALTER TABLE jtl_connector_product_checksum MODIFY endpoint_id VARCHAR(10)");
}
