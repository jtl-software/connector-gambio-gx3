<?php
namespace jtl\Connector\Gambio\Auth;

use \jtl\Connector\Authentication\ITokenLoader;

class TokenLoader implements ITokenLoader
{
    public function load()
    {
        return Application()->getConnector()->getConfig()->read('auth_token');
    }
}
