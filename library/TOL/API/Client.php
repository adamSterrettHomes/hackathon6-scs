<?php
/**
 * Defines the TOL_API_Client class
 *
 * @package TOL_API
 */

use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;
use DominionEnterprises\Util\Http;

/**
 * PHP Client for the TOL API
 *
 * @package TOL_API
 */
final class TOL_API_Client
{
    /**
     * Hostname of the API server
     *
     * @var string
     */
    private $_host;

    /**
     * Version of the API to use
     *
     * @var string
     */
    private $_version;

    /**
     * HTTP Adapter for sending request to the api
     *
     * @var TOL_API_Adapter_Interface
     */
    private $_adapter;

    /**
     * API client id
     *
     * @var string
     */
    private $_clientId;

    /**
     * API client secret
     *
     * @var string
     */
    private $_clientSecret;

    /**
     * API access token
     *
     * @var string
     */
    private $_accessToken;

    /**
     * Storage for cached API responses
     *
     * @var TOL_API_Cache
     */
    private $_cache;

    /**
     * Create a new instance of TOL_API_Client
     *
     * @param TOL_API_Adapter_Interface $adapter
     * @param string $clientId
     * @param string $clientSecret
     * @param string $host
     * @param string $version
     * @param TOL_API_Cache_Interface $cache
     *
     * @throws InvalidArgumentException Thrown if $clientId is not a non-empty string
     * @throws InvalidArgumentException Thrown if $clientSecret is not a non-empty string
     * @throws InvalidArgumentException Thrown if $host is not a non-empty string
     * @throws InvalidArgumentException Thrown if $version provided is not supported
     */
    public function __construct(
        TOL_API_Adapter_Interface $adapter,
        $clientId,
        $clientSecret,
        $host,
        $version,
        TOL_API_Cache_Interface $cache = null
    )
    {
        Util::throwIfNotType(array('string' => array($clientId, $clientSecret, $host, $version)), true);

        $this->_adapter = $adapter;
        $this->_host = $host;
        $this->_version = "v{$version}";
        $this->_clientId = $clientId;
        $this->_clientSecret = $clientSecret;
        $this->_cache = $cache;
    }

    /**
     * Gets an access token from the API
     *
     * @return string
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function getAccessToken()
    {
        if ($this->_accessToken === null) {
            $this->refreshAccessToken();
        }

        return $this->_accessToken;
    }

    /**
     * Obtains a new access token from the API
     *
     * @return void
     *
     * @throws TOL_API_Exception Thrown if API response cannot be evaluated
     * @throws TOL_API_Exception Thrown if API response http status is not 200
     */
    public function refreshAccessToken()
    {
        try {
            $data = array('client_id' => $this->_clientId, 'client_secret' => $this->_clientSecret, 'grant_type' => 'client_credentials');

            $url = "{$this->_host}/{$this->_version}/token";
            $request = new TOL_API_Request($url, 'POST', Http::buildQueryString($data));
            $response = $this->_adapter->request($request);
            $parsedJson = $response->getResponse();

            if ($response->getHttpCode() !== 200) {
                throw new TOL_API_Exception(Arrays::get($parsedJson, 'error_description', 'Unknown API error'));
            }

            $this->_accessToken = $parsedJson['access_token'];
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }

    /**
     * Helper method to send an API request.  A second attempt will be made
     * if authentication fails
     *
     * @param string $url
     * @param string $method
     * @param string $body
     * @param array $headers
     *
     * @return TOL_API_Response
     */
    private function _sendRequest($url, $method, $body = null, array $headers = array())
    {
        $headers['Authorization'] = "Bearer {$this->getAccessToken()}";
        $request = new TOL_API_Request($url, $method, $body, $headers);

        if ($this->_cache !== null && $request->getMethod() === 'GET') {
            $cached = $this->_cache->get($request);
            if ($cached !== null) {
                return $cached;
            }
        }

        $response = $this->_adapter->request($request);
        if ($response->getHttpCode() === 401) {
            $parsedJson = $response->getResponse();
            $error = Arrays::get($parsedJson, 'error');
            if ($error === 'invalid_grant') {
                $this->refreshAccessToken();
                $headers['Authorization'] = "Bearer {$this->getAccessToken()}";
                $request = new TOL_API_Request($url, $method, $body, $headers);
                $response = $this->_adapter->request($request);
            }
        }

        if ($this->_cache !== null && $request->getMethod() === 'GET') {
            $this->_cache->set($request, $response);
        }

        return $response;
    }

    /**
     * Search the API resource using the specified $filters
     *
     * @param string $resource
     * @param array $filters
     *
     * @return TOL_API_Response
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function index($resource, array $filters = array())
    {
        Util::throwIfNotType(array('string' => array($resource)), true);
        try {
            $url = "{$this->_host}/{$this->_version}/" . urlencode($resource) . '?' . Http::buildQueryString($filters);
            return $this->_sendRequest($url, 'GET');
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }

    /**
     * Get the details of an API resource based on $id
     *
     * @param string $resource
     * @param string $id
     *
     * @return TOL_API_Response
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function get($resource, $id)
    {
        Util::throwIfNotType(array('string' => array($resource, $id)), true);
        try {
            $url = "{$this->_host}/{$this->_version}/" . urlencode($resource) . '/' . urlencode($id);
            return $this->_sendRequest($url, 'GET');
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }

    /**
     * Create a new instance of an API resource using the provided $data
     *
     * @param string $resource
     * @param array $data
     *
     * @return TOL_API_Response
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function post($resource, array $data)
    {
        Util::throwIfNotType(array('string' => array($resource)), true);
        try {
            $url = "{$this->_host}/{$this->_version}/" . urlencode($resource);
            $json = json_encode($data);
            $headers = array('Content-Type' => 'application/json');
            return $this->_sendRequest($url, 'POST', $json, $headers);
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }

    /**
     * Update an existing instance of an API resource specified by $id with the provided $data
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return TOL_API_Response
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function put($resource, $id, array $data)
    {
        Util::throwIfNotType(array('string' => array($resource, $id)), true);
        try {
            $url = "{$this->_host}/{$this->_version}/" . urlencode($resource) . '/' . urlencode($id);
            $json = json_encode($data);
            $headers = array('Content-Type' => 'application/json');
            return $this->_sendRequest($url, 'PUT', $json, $headers);
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }

    /**
     * Delete an existing instance of an API resource specified by $id
     *
     * @param string $resource
     * @param string $id
     * @param array $data
     *
     * @return TOL_API_Response
     *
     * @throws TOL_API_Exception Thrown if the request cannot be made
     */
    public function delete($resource, $id, array $data = null)
    {
        Util::throwIfNotType(array('string' => array($resource, $id)), true);
        try {
            $url = "{$this->_host}/{$this->_version}/" . urlencode($resource) . '/' . urlencode($id);
            $json = $data !== null ? json_encode($data) : null;
            $headers = array('Content-Type' => 'application/json');
            return $this->_sendRequest($url, 'DELETE', $json, $headers);
        } catch (Exception $e) {
            throw new TOL_API_Exception('Unable to make request', 0, $e);
        }
    }
}
