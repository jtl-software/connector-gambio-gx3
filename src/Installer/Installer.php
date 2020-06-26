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
    
    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 1);
        
        $db = Mysql::getInstance();
        $this->configHelper = new ConfigHelper($db);

        $shopConfig = $this->configHelper->readGxConfigFile();
        $this->connectorConfig = new Config(CONNECTOR_DIR . '/config/config.json');


        if (!$db->isConnected()) {
            $db->connect([
                "host"     => $shopConfig['db']["host"],
                "user"     => $shopConfig['db']["user"],
                "password" => $shopConfig['db']["pass"],
                "name"     => $shopConfig['db']["name"],
            ]);
        }
        
        $db->setNames();
        
        $moduleInstances = [];
        
        foreach ($this->modules as $id => $module) {
            $className = '\\jtl\\Connector\\Gambio\\Installer\\Modules\\' . $module;
            $moduleInstances[$id] = new $className($db, $this->connectorConfig, $this->configHelper, $shopConfig);
        }
        
        if ($moduleInstances['check']->hasPassed()) {
            echo '<ul class="nav nav-tabs">';
            
            foreach ($moduleInstances as $class => $instance) {
                $active = $class == 'check' ? 'active' : '';
                echo '<li class="' . $active . '"><a href="#' . $class . '" data-toggle="tab"><b>' . $instance::$name . '</b></a></li>';
            }
            
            echo '</ul>
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
                
                echo '<div class="tab-pane' . $active . '" id="' . $class . '">';
                echo $instance->form();
                echo '</div>';
            }
            
            echo '</div>';
            
            if (isset($_REQUEST['save'])) {
                if (count($moduleErrors) == 0) {
                    if (!$this->connectorConfig->save()) {
                        echo '<div class="alert alert-danger">Fehler beim Schreiben der config.json Datei.</div>';
                    } else {
                        echo '<div class="alert alert-success">Connector Konfiguration wurde gespeichert.</div>';
                        echo '<div class="alert alert-danger"><b>ACHTUNG:</b><br/>
                            Bitte sorgen Sie nach erfolgreicher Installation des Connectors unbedingt dafür, dass dieser Installer 
                            sowie die Datei config.json im Verzeichnis config nicht öffentlich les- und ausführbar sind!</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">Folgende Fehler traten auf:
		        		<br>
		        		<ul>';
                    
                    foreach ($moduleErrors as $error) {
                        echo '<li>' . $error . '</li>';
                    }
                    
                    echo '</ul>
		        		</div>';
                }
            }
            
            echo '<button type="submit" name="save" class="btn btn-primary btn-block"><span class="glyphicon glyphicon-save"></span> Connector Konfiguration speichern</button>';
        } else {
            echo '<div class="alert alert-danger">Bitte beheben Sie die angezeigten Fehler bevor Sie mit der Konfiguration fortfahren können.</div>';
            echo $moduleInstances['check']->form();
        }
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
