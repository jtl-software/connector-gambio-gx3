<?php
namespace jtl\Connector\Gambio\Installer;

class Config
{
    private $data;

    public function __construct($file)
    {
        try{
            $this->data = \Noodlehaus\Config::load($file)->all();
        } catch (\Noodlehaus\Exception\FileNotFoundException $e) {
            $this->data = [];
            $this->data['ignore_custom_fields_as_attributes'] = false;
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
