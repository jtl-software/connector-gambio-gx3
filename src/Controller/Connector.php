<?php

namespace jtl\Connector\Gambio\Controller;

use jtl\Connector\Model\ConnectorServerInfo;
use jtl\Connector\Result\Action;
use jtl\Connector\Model\Statistic;
use jtl\Connector\Core\Controller\Controller;
use jtl\Connector\Core\Model\DataModel;
use jtl\Connector\Core\Model\QueryFilter;
use jtl\Connector\Model\ConnectorIdentification;
use jtl\Connector\Session\SessionHelper;

class Connector extends BaseController
{
    public function statistic(QueryFilter $filter)
    {
        $action = new Action();
        $action->setHandled(true);

        $return = [];

        $mainControllers = [
            'Category',
            'Customer',
            'CustomerOrder',
            'Image',
            'Product',
            'Manufacturer',
            'CrossSelling'
        ];

        foreach ($mainControllers as $controller) {
            $class = "\\jtl\\Connector\\Gambio\\Mapper\\{$controller}";

            if (class_exists($class)) {
                try {
                    $mapper = new $class();

                    $statModel = new Statistic();

                    $statModel->setAvailable($mapper->statistic());
                    $statModel->setControllerName(lcfirst($controller));

                    $return[] = $statModel;
                } catch (\Exception $exc) {
                    $err = new Error();
                    $err->setCode($exc->getCode());
                    $err->setMessage($exc->getMessage());
                    $action->setError($err);
                }
            }
        }

        $action->setResult($return);

        return $action;
    }

    public function pull(QueryFilter $queryfilter)
    {
    }

    public function push(DataModel $model)
    {
    }

    public function delete(DataModel $model)
    {
    }

    public function identify()
    {
        $action = new Action();
        $action->setHandled(true);

        $returnMegaBytes = function ($value) {
            $value = trim($value);
            $unit = strtolower($value[strlen($value) - 1]);
            switch ($unit) {
                case 'g':
                    $value *= 1024;
            }

            return (int) $value;
        };

        $serverInfo = new ConnectorServerInfo();
        $serverInfo->setMemoryLimit($returnMegaBytes(ini_get('memory_limit')))
            ->setExecutionTime((int) ini_get('max_execution_time'))
            ->setPostMaxSize($returnMegaBytes(ini_get('post_max_size')))
            ->setUploadMaxFilesize($returnMegaBytes(ini_get('upload_max_filesize')));

        $connector = new ConnectorIdentification();
        $connector->setEndpointVersion(CONNECTOR_VERSION);
        $connector->setPlatformName('Gambio');
        $connector->setPlatformVersion($this->shopConfig['shop']['version']);
        $connector->setProtocolVersion(Application()->getProtocolVersion());
        $connector->setServerInfo($serverInfo);

        $action->setResult($connector);

        return $action;
    }
}
