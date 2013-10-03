<?php
/**
 * Defines the TOL_API_Response class
 *
 * @package TOL_API
 */

use DominionEnterprises\Util;

/**
 * Represents a response to an API request
 *
 * @package TOL_API
 */
final class TOL_API_Response
{
    /**
     * The http status of the response.
     *
     * @var int
     */
    private $_httpCode;

    /**
     * The response from the API
     *
     * @var array
     */
    private $_body;

    /**
     * A array of headers received with the response.
     *
     * @var array
     */
    private $_headers;

    /**
     * Create a new instance of TOL_API_Response
     *
     * @param int $httpCode
     * @param array $headers
     * @param array $body
     *
     * @throws InvalidArgumentException Throw if $httpCode is not an integer between 100 and 600
     */
    public function __construct($httpCode, array $headers, array $body = array())
    {
        Util::throwIfNotType(array('int' => $httpCode));

        if ($httpCode < 100 || $httpCode > 600) {
            throw new InvalidArgumentException('$httpCode must be an integer >= 100 and <= 600');
        }

        $this->_httpCode = $httpCode;
        $this->_headers = $headers;
        $this->_body = $body;
    }

    /**
     * Returns the HTTP status code of the response
     *
     * @return int
     */
    public function getHttpCode()
    {
        return $this->_httpCode;
    }

    /**
     * Returns an array representing the response from the API
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->_body;
    }

    /**
     * Returns the parsed response headers from the API
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->_headers;
    }
}
