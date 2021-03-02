<?php

namespace jtl\Connector\Gambio;

use jtl\Connector\Core\Exception\DatabaseException;
use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Core\Utilities\RpcMethod;
use \jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Gambio\Controller\BaseController;
use jtl\Connector\Gambio\Gambio\Application;
use jtl\Connector\Gambio\Util\ConfigHelper;
use jtl\Connector\Gambio\Util\ShopVersion;
use jtl\Connector\Model\DeliveryNote;
use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductPrice;
use jtl\Connector\Model\ProductStockLevel;
use jtl\Connector\Model\StatusChange;
use \jtl\Connector\Session\SessionHelper;
use \jtl\Connector\Base\Connector as BaseConnector;
use \jtl\Connector\Core\Rpc\Method;
use \jtl\Connector\Gambio\Mapper\PrimaryKeyMapper;
use \jtl\Connector\Result\Action;
use \jtl\Connector\Gambio\Auth\TokenLoader;
use \jtl\Connector\Gambio\Checksum\ChecksumLoader;

class Connector extends BaseConnector
{
    /**
     * @var Application
     */
    protected static $gxApplication;

    /**
     * @var BaseController
     */
    protected $controller;

    /**
     * @var string
     */
    protected $action;

    /**
     * @throws DatabaseException
     */
    public function initialize()
    {
        $db = Mysql::getInstance();
        $configHelper = new ConfigHelper($db);
        $session = new SessionHelper("gambio");

        if (!isset($session->shopConfig)) {
            $session->shopConfig = $configHelper->readGxConfigFile();
        }

        ShopVersion::setShopVersion($session->shopConfig['shop']['version']);

        if (!isset($session->connectorConfig)) {
            $session->connectorConfig = json_decode(@file_get_contents(CONNECTOR_DIR.'/config/config.json'));
        }

        if (!$db->isConnected()) {
            $db->connect([
                "host" => $session->shopConfig['db']["host"],
                "user" => $session->shopConfig['db']["user"],
                "password" => $session->shopConfig['db']["pass"],
                "name" => $session->shopConfig['db']["name"]
            ]);
        }

        if (!isset($session->connectorConfig->utf8) || $session->connectorConfig->utf8 !== '1') {
            $db->setNames();
            $db->setCharset();
        }

        if (!isset($session->shopConfig['settings'])) {
            $session->shopConfig['settings'] = $configHelper->getDefaultDbConfig();
        }

        $this->update($db);

        $this->setPrimaryKeyMapper(new PrimaryKeyMapper());
        $this->setTokenLoader(new TokenLoader());
        $this->setChecksumLoader(new ChecksumLoader());
    }

    private function update($db)
    {
        if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), CONNECTOR_VERSION) == -1) {
            $versions = [];
            foreach (new \DirectoryIterator(CONNECTOR_DIR.'/db/updates') as $item) {
                if ($item->isFile()) {
                    $versions[] = $item->getBasename('.php');
                }
            }

            sort($versions);

            foreach ($versions as $version) {
                if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), $version) == -1) {
                    include(CONNECTOR_DIR.'/db/updates/' . $version . '.php');
                }
            }
        }
    }

    public function canHandle()
    {
        $controller = RpcMethod::buildController($this->getMethod()->getController());
        $class = "\\jtl\\Connector\\Gambio\\Controller\\{$controller}";

        if (class_exists($class)) {
            $this->controller = $class::getInstance();
            $this->action = RpcMethod::buildAction($this->getMethod()->getAction());

            return is_callable([$this->controller, $this->action]);
        }

        return false;
    }

    public function handle(RequestPacket $requestpacket)
    {
        $this->controller->setMethod($this->getMethod());

        $result = [];

        if ($this->action === Method::ACTION_PUSH || $this->action === Method::ACTION_DELETE) {
            if (!is_array($requestpacket->getParams())) {
                throw new \Exception('data is not an array');
            }

            $action = new Action();
            $results = [];

            $link = Mysql::getInstance();
            $link->DB()->begin_transaction();
            
            foreach ($requestpacket->getParams() as $param) {
                $result = $this->controller->{$this->action}($param);

                $reflectionClass = new \ReflectionClass($param);
    
                if ($result->getError()) {
                    $link->rollback();
                    $message = sprintf('Type: %s %s', get_class($param), $result->getError()->getMessage());
                    
                    if ($param instanceof Product) {
                        $message = sprintf('Type: Product Host-Id: %s SKU: %s %s', $param->getId()->getHost(), $param->getSku(), $result->getError()->getMessage());
                    } elseif ($param instanceof ProductPrice || $param instanceof ProductStockLevel) {
                        $message = sprintf('Type: %s Product Host-Id: %s %s', $reflectionClass->getShortName(), $param->getProductId()->getHost(), $result->getError()->getMessage());
                    } elseif ($param instanceof StatusChange || $param instanceof DeliveryNote) {
                        $message = sprintf('Type: %s Order Host-Id: %s %s', $reflectionClass->getShortName(), $param->getCustomerOrderId()->getHost(), $result->getError()->getMessage());
                    } elseif (method_exists($param, 'getId')) {
                        $message = sprintf('Type: %s Host-Id: %s %s', $reflectionClass->getShortName(), $param->getId()->getHost(), $result->getError()->getMessage());
                    }
                    
                    throw new \Exception($message);
                }
                
                $results[] = $result->getResult();
            }

            $link->commit();
            
            $action->setHandled(true)
                ->setResult($results)
                ->setError($result->getError());

            return $action;
        } else {
            return $this->controller->{$this->action}($requestpacket->getParams());
        }
    }

    /**
     * @return void
     */
    protected static function initGxApplication(): void
    {
        if(is_null(self::$gxApplication)) {
            self::$gxApplication = new Application();
            self::$gxApplication->run();
        }
    }

    /**
     * @param string $serviceName
     * @return object
     */
    public static function getGxService(string $serviceName)
    {
        self::initGxApplication();
        /** @var \OrderWriteService $service */
        //$service = \StaticGXCoreLoader::getService('OrderWrite');
        return \StaticGXCoreLoader::getService($serviceName);
    }
}
