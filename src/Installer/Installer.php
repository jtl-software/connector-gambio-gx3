<?php

namespace jtl\Connector\Gambio\Installer;

use jtl\Connector\Core\Database\Mysql;
use jtl\Connector\Gambio\Installer\Config;
use jtl\Connector\Gambio\Util\ConfigHelper;

class Installer
{
    private $modules = [
        'check'       => 'Check',
        'connector'   => 'Connector',
        'status'      => 'Status',
        'thumbs'      => 'ThumbMode',
        'tax_rate'    => 'TaxRate',
        'dev_logging' => 'DevLogging',
    
    ];
    
    private $connectorConfig = null;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    protected $db;

    protected $shopConfig;
    
    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 1);
        
        $this->db = Mysql::getInstance();
        $this->configHelper = new ConfigHelper($this->db);

        $this->shopConfig = $this->configHelper->readGxConfigFile();
        $this->connectorConfig = new Config(CONNECTOR_DIR . '/config/config.json');


        if (!$this->db->isConnected()) {
            $this->db->connect([
                "host"     => $this->shopConfig['db']["host"],
                "user"     => $this->shopConfig['db']["user"],
                "password" => $this->shopConfig['db']["pass"],
                "name"     => $this->shopConfig['db']["name"],
            ]);
        }
        
        $this->db->setNames();
    }

    /**
     * @return string
     */
    public function runAndGetData(): string
    {
        $moduleInstances = [];

        $html = '';
        foreach ($this->modules as $id => $module) {
            $className = '\\jtl\\Connector\\Gambio\\Installer\\Modules\\' . $module;
            $moduleInstances[$id] = new $className($this->db, $this->connectorConfig, $this->configHelper, $this->shopConfig);
        }

        if ($moduleInstances['check']->hasPassed()) {
            $html .= '<ul class="nav nav-tabs">';

            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? 'active' : '';
                $html .= '<li class="' . $active . '"><a href="#' . $class . '" data-toggle="tab"><b>' . $instance::$name . '</b></a></li>';
            }

            $html .= '</ul>
	        	<br>
	        	<div class="tab-content">';

            $moduleErrors = [];

            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? ' active' : '';

                if (isset($_REQUEST['save'])) {
                    $moduleSave = $instance->save();
                    if ($moduleSave !== true) {
                        $moduleErrors[] = $moduleSave;
                    }
                }

                $html .= '<div class="tab-pane' . $active . '" id="' . $class . '">';
                $html .= $instance->form();
                $html .= '</div>';
            }

            $html .= '</div>';

            if (isset($_REQUEST['save'])) {
                if (count($moduleErrors) == 0) {
                    if (!$this->connectorConfig->save()) {
                        $html .= '<div class="alert alert-danger">Fehler beim Schreiben der config.json Datei.</div>';
                    } else {
                        $html .= '<div class="alert alert-success">Connector Konfiguration wurde gespeichert.</div>';
                        $html .= '<div class="alert alert-danger"><b>ACHTUNG:</b><br/>
                            Bitte sorgen Sie nach erfolgreicher Installation des Connectors unbedingt dafür, dass dieser Installer 
                            sowie die Datei config.json im Verzeichnis config nicht öffentlich les- und ausführbar sind!</div>';
                    }
                } else {
                    $html .= '<div class="alert alert-danger">Folgende Fehler traten auf:
		        		<br>
		        		<ul>';

                    foreach ($moduleErrors as $error) {
                        $html .= '<li>' . $error . '</li>';
                    }

                    $html .= '</ul>
		        		</div>';
                }
            }

            $html .= '<button type="submit" name="save" class="btn btn-primary btn-block"><span class="glyphicon glyphicon-save"></span> Connector Konfiguration speichern</button>';
        } else {
            $html .= '<div class="alert alert-danger">Bitte beheben Sie die angezeigten Fehler bevor Sie mit der Konfiguration fortfahren können.</div>';
            $html .= $moduleInstances['check']->form();
        }

        return $html;
    }
    
    private function readConfigFile()
    {
        require_once realpath(CONNECTOR_DIR . '/../') . '/includes/configure.php';
        
        return [
            'shop' => [
                'url'     => HTTP_SERVER,
                'folder'  => DIR_WS_CATALOG,
                'fullUrl' => HTTP_SERVER . DIR_WS_CATALOG,
            ],
            'db'   => [
                'host' => DB_SERVER,
                'name' => DB_DATABASE,
                'user' => DB_SERVER_USERNAME,
                'pass' => DB_SERVER_PASSWORD,
            ],
            'img'  => [
                'original' => DIR_WS_ORIGINAL_IMAGES,
            ],
        ];
    }
}
