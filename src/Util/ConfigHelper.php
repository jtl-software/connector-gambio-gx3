<?php


namespace jtl\Connector\Gambio\Util;


use jtl\Connector\Core\Database\Mysql;

class ConfigHelper
{
    /** @var Mysql */
    protected $db;

    /**
     * ConfigHelper constructor.
     * @param Mysql $db
     * @throws \Exception
     */
    public function __construct(Mysql $db)
    {
        if (!defined('CONNECTOR_DIR')) {
            throw new \Exception('Constant CONNECTOR_DIR is not defined');
        }
        $this->db = $db;
    }

    /**
     * @return array[]
     * @throws \Exception
     */
    public function readGxConfigFile(): array
    {
        $gxConfigFile = sprintf('%s/includes/configure.php', dirname(CONNECTOR_DIR));
        if (!file_exists($gxConfigFile)) {
            throw new \Exception(sprintf('Gambio configuration file not found in "%s"', $gxConfigFile));
        }

        $gxVersionFile = sprintf('%s/release_info.php', dirname(CONNECTOR_DIR));
        if (!file_exists($gxVersionFile)) {
            throw new \Exception(sprintf('Gambio version file not found in "%s"', $gxVersionFile);
        }

        $gx_version = '';
        require_once($gxConfigFile);
        require_once($gxVersionFile);

        $version = ltrim($gx_version, 'v');

        return array(
            'shop' => array(
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'path' => DIR_FS_DOCUMENT_ROOT,
                'fullUrl' => HTTP_SERVER . DIR_WS_CATALOG,
                'version' => $version
            ),
            'db' => array(
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD
            ),
            'img' => array(
                'original' => DIR_WS_ORIGINAL_IMAGES,
                'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
                'info' => DIR_WS_INFO_IMAGES,
                'popup' => DIR_WS_POPUP_IMAGES,
                'gallery' => 'images/product_images/gallery_images/'
            )
        );
    }

    /**
     * @param $db
     * @return array[]
     */
    public function readGxConfigDb(): array
    {
        $key = 'configuration_key';
        $value = 'configuration_value';
        $table = 'configuration';
        $where = '';

        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $key = 'key';
            $value = 'value';
            $table = 'gx_configurations';
            $where = 'WHERE language_id IS NULL AND `key` LIKE "configuration/%"';
        }

        $configDb = $this->db->query(sprintf("SElECT `%s`,`%s` FROM `%s` %s", $key, $value, $table, $where));
        $return = array();

        foreach ($configDb as $entry) {
            if (ShopVersion::isGreaterOrEqual('4.1')) {
                $entry[$key] = str_replace('configuration/', '', $entry[$key]);
            }
            $return[$entry[$key]] = $entry[$value] == 'true' ? 1 : ($entry[$value] == 'false' ? 0 : $entry[$value]);
        }

        return array(
            'settings' => $return
        );
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function getDbConfigValue(string $key)
    {
        $column = 'configuration_value';
        $table = 'configuration';
        $where = 'configuration_key';

        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $column = 'value';
            $table = 'gx_configurations';
            $where = 'key';
            $key = sprintf('configuration/%s', $key);
        }

        return $this->db->query(sprintf('SELECT `%s` as configuration_value FROM `%s` WHERE `%s` = "%s"', $column, $table, $where, $key));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    public function updateDbConfigValue(string $key, $value)
    {
        $column = 'configuration_value';
        $table = 'configuration';
        $where = 'configuration_key';

        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $column = 'value';
            $table = 'gx_configurations';
            $where = 'key';
            $key = sprintf('configuration/%s', $key);
        }

        $sql = sprintf('UPDATE `%s` SET `%s` = "%s" WHERE `%s` = "%s"', $table, $column, $value, $where, $key);
        $this->db->query($sql);
    }
}