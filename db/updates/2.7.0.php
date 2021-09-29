<?php
$db->query('CREATE TABLE IF NOT EXISTS jtl_connector_link_specific (
  endpoint_id INT(10) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');

$db->query('CREATE TABLE IF NOT EXISTS jtl_connector_link_specific_value (
  endpoint_id INT(10) NOT NULL,
  host_id INT(10) NOT NULL,
  PRIMARY KEY (endpoint_id),
  INDEX (host_id)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');

