<?php
/**
 * Defines the TOL_API_Adapter_Interface interface
 *
 * @package TOL_API
 */

/**
 * interface for a TOL api client.
 *
 * @package TOL_API
 */
interface TOL_API_Adapter_Interface
{
    /**
     * Execute the specified request against the API
     *
     * @param TOL_API_Request $request
     *
     * @return TOL_API_Response_Interface
     */
    public function request(TOL_API_Request $request);
}
