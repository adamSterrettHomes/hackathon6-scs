<?php
/**
 * Defines the TOL_API_Adapter class
 *
 * @package TOL_API
 */

use DominionEnterprises\Util;
use DominionEnterprises\Util\Http;

/**
 * Concrete implentation of TOL_API_Adapter_Interface
 *
 * @package TOL_API
 */
final class TOL_API_Adapter_Curl implements TOL_API_Adapter_Interface
{
    /**
     * @see TOL_API_Adapter_Interface::request()
     *
     * @throws TOL_API_Exception Thrown if unable to make the HTTP request
     */
    public function request(TOL_API_Request $request)
    {
        $curlHeaders = array('Expect:');//stops curl automatically putting in expect 100.
        foreach ($request->getHeaders() as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }

        $curlOptions = array(
            CURLOPT_URL => $request->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_HEADER => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
        );

        $method = strtoupper($request->getMethod());
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
            case 'PUT':
            case 'DELETE':
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                $curlOptions[CURLOPT_POSTFIELDS] = $request->getBody();
                break;
            default:
                throw new TOL_API_Exception("Unsupported method '{$method}' given");
        }

        $curl = Util::ensureNot(false, curl_init(), 'TOL_API_Exception', array('Unable to initial connection to API'));

        Util::ensureNot(false, curl_setopt_array($curl, $curlOptions), 'TOL_API_Exception', array('Unable to establish connection'));

        $result = curl_exec($curl);
        Util::ensureNot(false, $result, 'TOL_API_Exception', array(curl_error($curl)));

        $headerSize = Util::ensureNot(
            false,
            curl_getinfo($curl, CURLINFO_HEADER_SIZE),
            'TOL_API_Exception',
            array('Unable to determine header size')
        );

        $httpCode = Util::ensureNot(
            false,
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
            'TOL_API_Exception',
            array('Unable to determine response HTTP code')
        );

        $rawHeaders = substr($result, 0, $headerSize - 1);
        $headers = Http::parseHeaders($rawHeaders);

        $rawResult = trim(substr($result, $headerSize));
        // ensure body is always an array
        $body = $rawResult === '' ? array() : json_decode($rawResult, true);
        // only throw when json_last_error() reports an error
        Util::ensure(JSON_ERROR_NONE, json_last_error(), 'TOL_API_Exception', array('Unable to parse response body'));

        return new TOL_API_Response($httpCode, $headers, $body);
    }
}
