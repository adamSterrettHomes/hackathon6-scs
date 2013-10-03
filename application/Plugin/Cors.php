<?php
final class Plugin_Cors extends Zend_Controller_Plugin_Abstract
{
    /**
     * This callback sets the CORS origin header to allow requests to our API from any domain.
     *
     * @param Zend_Controller_Request_Abstract $request The zend request
     *
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_response->setHeader('Access-Control-Allow-Origin', '*', true);
    }
}
