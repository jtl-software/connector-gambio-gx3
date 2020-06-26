<?php

namespace jtl\Connector\Gambio\Installer\Modules;

use jtl\Connector\Core\Config\Config;
use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Gambio\Installer\Module;
use jtl\Connector\Gambio\Util\ConfigHelper;
use jtl\Connector\Gambio\Util\ShopVersion;

class Status extends Module
{
    public static $name = '<span class="glyphicon glyphicon-random"></span> Status Zuordnung';

    private $gambioStats = null;
    private $defaultLanguage = null;

    private $jtlStats = array(
        'paid' => 'Bezahlt',
        'shipped' => 'Versendet',
        'completed' => 'Bezahlt &amp; Versendet',
        'canceled' => 'Storniert'
    );

    public function __construct(Mysql $db, Config $config, ConfigHelper $configHelper, array $shopConfig)
    {
        parent::__construct($db, $config, $configHelper, $shopConfig);
        $customerOrderModel = new \ReflectionClass('\jtl\Connector\Model\CustomerOrder');
        $this->defaultLanguage = $this->getDefaultShopLanguage();

        //Filtering the order status with id 1 so that status 'open' can't be mapped twice
        $this->gambioStats = $this->db->query('SELECT * FROM orders_status WHERE language_id=' . $this->defaultLanguage . " && orders_status_id != 1");
    }

    public function form()
    {
        $default = $this->getDefaultOrderStatusName();

        $html = '<div class="alert alert-info">Für jeden Auftrags-Zustand aus der Wawi muss hier der zugehörige Shop-Zustand konfiguriert werden. <b>Bitte beachten Sie dass jeder Zustand eindeutig sein muss.</b></div>';
        $html .= '<a class="btn btn-default btn-sm btn-block" href="' . $this->shopConfig['shop']['fullUrl'] . 'admin/orders_status.php">Shop-Status anlegen und verwalten</a>';
        $html .= '<div class="form-group">
                    <label class="col-sm-2 control-label">Neu</label>
                        <div class="col-sm-3">
                            <p class="form-control-static">' . $default . ' (Standard-Status Ihres Shops)</p>
                        </div>
                </div>';

        foreach ($this->jtlStats as $key => $value) {
            $mapping = (array)$this->config->mapping;

            $stats = '';

            foreach ($this->gambioStats as $gambio) {
                $selected = ($mapping[$key] == $gambio['orders_status_id']) ? ' selected="selected"' : '';
                $stats .= '<option value="' . $gambio['orders_status_id'] . '"' . $selected . '>' . $gambio['orders_status_name'] . '</option>';
            }

            $html .= '<div class="form-group">
                    <label class="col-sm-2 control-label">' . $value . '</label>
                        <div class="col-sm-3">
                            <select class="form-control" name="status[' . $key . ']">' . $stats . '</select>
                        </div>
                </div>';
        }

        return $html;
    }

    public function save()
    {
        if (count(array_unique($_REQUEST['status'])) < count($_REQUEST['status'])) {
            return 'Bitte legen Sie für jeden Status eine eindeutige Shop-Zuweisung fest. Wenn ihr Shop derzeit nicht über genügend Status verfügt, legen Sie bitte die notwendigen zusätzlich an.';
        } else {
            $this->config->mapping = $_REQUEST['status'];

            return true;
        }
    }

    /**
     * @return mixed
     */
    protected function getDefaultShopLanguage()
    {
        $languagesCode = $this->configHelper->getDbConfigValue('DEFAULT_LANGUAGE');
        $sql = sprintf('SELECT `languages_id` FROM `languages` WHERE `code` = "%s"', $languagesCode);
        $result =  $this->db->query($sql);
        return isset($result[0]['languages_id']) ? $result[0]['languages_id'] : null;
    }

    /**
     * @return mixed
     */
    protected function getDefaultOrderStatusName()
    {
        $ordersStatusId = $this->configHelper->getDbConfigValue('DEFAULT_ORDERS_STATUS_ID');
        $sql = sprintf('SELECT `orders_status_name` FROM `orders_status` WHERE `orders_status_id` = "%s"', $ordersStatusId);
        $result = $this->db->query($sql);
        return isset($result[0]['orders_status_name']) ? $result[0]['orders_status_name'] : '';
    }
}
