<?php

namespace jtl\Connector\Gambio;

use jtl\Connector\Core\Rpc\Error;
use \jtl\Connector\Core\Rpc\RequestPacket;
use \jtl\Connector\Core\Utilities\RpcMethod;
use \jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Event\Connector\ConnectorAfterFinishEvent;
use jtl\Connector\Gambio\Controller\DefaultController;
use jtl\Connector\Gambio\Util\ConfigHelper;
use jtl\Connector\Gambio\Util\ShopVersion;
use jtl\Connector\Model\DataModel;
use jtl\Connector\Model\Image;
use \jtl\Connector\Session\SessionHelper;
use \jtl\Connector\Base\Connector as BaseConnector;
use \jtl\Connector\Core\Rpc\Method;
use \jtl\Connector\Gambio\Mapper\PrimaryKeyMapper;
use \jtl\Connector\Result\Action;
use \jtl\Connector\Gambio\Auth\TokenLoader;
use \jtl\Connector\Gambio\Checksum\ChecksumLoader;

class Connector extends BaseConnector
{
    public const
        FINISH_TASK_CLEANUP_PRODUCT_PROPERTIES = 'cleanup_product_properties';

    /**
     * @var DefaultController
     */
    protected $controller;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var
     */
    protected $shopConfig;

    /**
     * @var
     */
    protected $connectorConfig;

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

        $this->shopConfig = $session->shopConfig;
        $this->connectorConfig = $session->connectorConfig;

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

        $this->getEventDispatcher()->addListener(ConnectorAfterFinishEvent::EVENT_NAME, function (ConnectorAfterFinishEvent $event) use ($db) {
            if (isset($_SESSION[self::FINISH_TASK_CLEANUP_PRODUCT_PROPERTIES]) && $_SESSION[self::FINISH_TASK_CLEANUP_PRODUCT_PROPERTIES] === true) {
                $queries = [
                    'DELETE pv FROM properties_values pv WHERE pv.properties_values_id NOT IN (SELECT properties_values_id FROM products_properties_combis_values);',
                    'DELETE pvd FROM properties_values_description pvd WHERE pvd.properties_values_id NOT IN (SELECT properties_values_id FROM properties_values);',
                    'DELETE p FROM properties p WHERE p.properties_id NOT IN (SELECT properties_id FROM properties_values);',
                    'DELETE pd FROM properties_description pd WHERE pd.properties_id NOT IN (SELECT properties_id FROM properties);'
                ];

                foreach ($queries as $sql) {
                    $db->query($sql);
                }

                $_SESSION[self::FINISH_TASK_CLEANUP_PRODUCT_PROPERTIES] = false;
            }
        });
    }

    /**
     * @param Mysql $db
     */
    protected function update(Mysql $db): void
    {
        if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), CONNECTOR_VERSION) == -1) {
            $versions = [];
            foreach (new \DirectoryIterator(CONNECTOR_DIR.'/db/updates') as $item) {
                if ($item->isFile()) {
                    $versions[] = $item->getBasename('.php');
                }
            }

            usort($versions, 'version_compare');

            foreach ($versions as $version) {
                if (version_compare(file_get_contents(CONNECTOR_DIR.'/db/version'), $version) == -1) {
                    include(CONNECTOR_DIR.'/db/updates/' . $version . '.php');
                    file_put_contents(CONNECTOR_DIR.'/db/version', $version);
                }
            }
        }
    }

    public function canHandle()
    {
        $controllers = [
            'Category',
            'CrossSelling',
            'Customer',
            'CustomerOrder',
            'GlobalData',
            'Image',
            'Manufacturer',
            'Payment',
            'Product',
            'ProductPrice',
            'ProductStockLevel',
            'StatusChange',
        ];

        $controllerName = RpcMethod::buildController($this->getMethod()->getController());
        $db = Mysql::getInstance();

        $controllerClass = sprintf('jtl\\Connector\\Gambio\\Controller\\%s', $controllerName);

        if (class_exists($controllerClass)) {
            $this->controller = new $controllerClass($db, $this->shopConfig, $this->connectorConfig);
        } elseif (in_array($controllerName, $controllers, true)) {
            $this->controller = (new DefaultController($db, $this->shopConfig, $this->connectorConfig))->setControllerName($controllerName);
        }

        if (!is_null($this->controller)) {
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
                throw new \Exception('Data is not an array');
            }

            $action = new Action();
            $results = [];

            $link = Mysql::getInstance();
            $link->DB()->begin_transaction();

            /** @var DataModel $model */
            foreach ($requestpacket->getParams() as $model) {
                $result = $this->controller->{$this->action}($model);

                if ($result->getError()) {
                    $link->rollback();

                    if ($result->getError()) {
                        $this->extendErrorMessage($model, $result->getError());
                        throw new \Exception($result->getError()->getMessage());
                    }
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
     * @param DataModel $model
     * @param Error $error
     */
    protected function extendErrorMessage(DataModel $model, Error $error)
    {
        $controllerToIdentityGetter = [
            'ProductPrice' => 'getProductId',
            'ProductStockLevel' => 'getProductId',
            'StatusChange' => 'getCustomerOrderId',
            'DeliveryNote' => 'getCustomerOrderId',
            'Image' => 'getForeignKey',
        ];

        $controllerName = (new \ReflectionClass($this->controller))->getShortName();

        $identityGetter = $controllerToIdentityGetter[$controllerName] ?? 'getId';
        $identity = null;
        if (method_exists($model, $identityGetter)) {
            $identity = $model->{$identityGetter}();
        }

        if ($identity !== null) {
            $messageParts = [$controllerName];

            if ($model instanceof Image) {
                $messageParts[] = sprintf('Related type %s (hostId = %d)', ucfirst($model->getRelationType()), $identity->getHost());
            } else {
                $messageParts[] = sprintf('hostId = %d', $identity->getHost());
            }

            $messageParts[] = $error->getMessage();
            $error->setMessage(implode(' | ', $messageParts));
        }
    }
}
