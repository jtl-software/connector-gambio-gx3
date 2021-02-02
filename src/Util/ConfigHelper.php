<?php


namespace jtl\Connector\Gambio\Util;

use jtl\Connector\Core\Database\Mysql;

class ConfigHelper
{
    /** @var Mysql */
    protected $db;

    /**
     * @var array<mixed>
     */
    protected $storage;

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
            throw new \Exception(sprintf('Gambio version file not found in "%s"', $gxVersionFile));
        }

        $gx_version = '';
        require_once($gxConfigFile);
        require_once($gxVersionFile);

        $version = ltrim($gx_version, 'v');
        ShopVersion::setShopVersion($version);

        return [
            'shop' => [
                'url' => HTTP_SERVER,
                'folder' => DIR_WS_CATALOG,
                'path' => DIR_FS_DOCUMENT_ROOT,
                'fullUrl' => HTTP_SERVER . DIR_WS_CATALOG,
                'version' => $version
            ],
            'db' => [
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD
            ],
            'img' => [
                'original' => DIR_WS_ORIGINAL_IMAGES,
                'thumbnails' => DIR_WS_THUMBNAIL_IMAGES,
                'info' => DIR_WS_INFO_IMAGES,
                'popup' => DIR_WS_POPUP_IMAGES,
                'gallery' => 'images/product_images/gallery_images/'
            ]
        ];
    }

    /**
     * @return array[]
     * @throws \Exception
     */
    public function getDefaultDbConfig(): array
    {
        $table = 'configuration';
        $key = 'configuration_key';
        $value = 'configuration_value';
        $prefix = null;

        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $prefix = 'configuration/';
        }

        return $this->readDbConfig($table, $key, $value, $prefix);
    }

    /**
     * @param string $table
     * @param string $keyColumn
     * @param string $valueColumn
     * @param string|null $prefix
     * @return array
     * @throws \Exception
     */
    public function readDbConfig(string $table, string $keyColumn, string $valueColumn, string $prefix = null): array
    {
        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $keyColumn = 'key';
            $valueColumn = 'value';
            $table = 'gx_configurations';
        }

        $storageKey = $table;
        if (!is_null($prefix)) {
            $storageKey .= '||' . $prefix;
        }

        if (!isset($this->storage[$storageKey])) {
            $columns = [sprintf('`%s` `key`', $keyColumn), sprintf('`%s` `value`', $valueColumn)];

            $where = [];
            if (!is_null($prefix)) {
                $where[] = sprintf('`key` LIKE "%s%%"', $prefix);
            }

            if (count($where) === 0) {
                $where[] = '1';
            }

            $sql = sprintf('SELECT %s FROM %s WHERE %s', implode(',', $columns), $table, implode(' AND ', $where));

            $rows = $this->db->query($sql);

            $data = [];
            foreach ($rows as $row) {
                $key = is_null($prefix) ? $row['key'] : substr($row['key'], strlen($prefix));

                switch ($row['value']) {
                    case 'true':
                        $data[$key] = 1;
                        break;
                    case 'false':
                        $data[$key] = 0;
                        break;
                    default:
                        $data[$key] = $row['value'];
                        break;
                }
            }

            $this->storage[$storageKey] = $data;
        }

        return $this->storage[$storageKey];
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws \Exception
     */
    public function getDbConfigValue(string $key, $default = null)
    {
        $prefix = null;
        if (ShopVersion::isGreaterOrEqual('4.1')) {
            $prefix = 'configuration/';
        }

        $config = $this->readDbConfig('configuration', 'configuration_key', 'configuration_value', $prefix);

        return $config[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    public function updateGxDbConfigValue(string $key, $value)
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

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function getDefaultLanguage()
    {
        $languagesCode = $this->getDbConfigValue('DEFAULT_LANGUAGE');
        $sql = sprintf('SELECT `languages_id` FROM `languages` WHERE `code` = "%s"', $languagesCode);
        $result = $this->db->query($sql);
        return isset($result[0]['languages_id']) ? $result[0]['languages_id'] : null;
    }
}
