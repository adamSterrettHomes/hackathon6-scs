<?php
/**
 * Defines the TOL_API_Cache_Interface interface
 *
 * @package TOL_API
 */

/**
 * Interface for caching API responses
 *
 * @package TOL_API
 */
interface TOL_API_Cache_Interface
{
    /**
     * Store the api $response as the cached result of the api $request
     *
     * @param TOL_API_Request $request the request for which the response will be cached
     * @param TOL_API_Response $response the reponse to cache
     *
     * @return void
     */
    public function set(TOL_API_Request $request, TOL_API_Response $response);

    /**
     * Retrieve the cached results of the api $request
     *
     * @param TOL_API_Request $request a request for which the response may be cached
     *
     * @return TOL_API_Response|null
     */
    public function get(TOL_API_Request $request);
}
