<?php
namespace jtl\Connector\Gambio\Installer;

class Config
{
    private $data;
    const IGNORE_CUSTOM_FIELDS = 'ignore_custom_fields';
    
    public function __construct($file)
    {
        try{
            $this->data = \Noodlehaus\Config::load($file)->all();
        } catch (\Noodlehaus\Exception\FileNotFoundException $e) {
            $this->data = [];
        } finally {
            if (!isset($this->data[self::IGNORE_CUSTOM_FIELDS])) {
                $this->data[self::IGNORE_CUSTOM_FIELDS] = false;
            }
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function save()
    {
        if (file_put_contents(CONNECTOR_DIR.'/config/config.json', json_encode($this->data)) === false) {
            return false;
        } else {
            return true;
        }
    }
}
