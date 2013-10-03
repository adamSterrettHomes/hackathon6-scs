<?php
/**
 * Defines the TOL_API_Cache class
 *
 * @package TOL_API
 */

use DominionEnterprises\Util;
use DominionEnterprises\Util\Arrays;

/**
 * Class to store API results
 *
 * @package TOL_API
 */
final class TOL_API_Cache_Mongo implements TOL_API_Cache_Interface
{
    /**
     * Mongo collection for storing cache
     *
     * @var MongoCollection
     */
    private $_collection;

    /**
     * Construct a new instance of TOL_API_Cache_Mongo
     *
     * @param string $url mongo url
     * @param string $db name of mongo database
     * @param string $collection name of mongo collection
     */
    public function __construct($url, $db, $collection)
    {
        Util::ensure(true, extension_loaded('mongo'), 'RuntimeException', array('mongo extension is required for ' . __CLASS__));
        Util::throwIfNotType(array('string' => array($url, $db, $collection)), true);
        $mongo = new MongoClient($url);
        $this->_collection = $mongo->selectDb($db)->selectCollection($collection);
    }

    /**
     * @see TOL_API_Cache_Interface::set()
     */
    public function set(TOL_API_Request $request, TOL_API_Response $response)
    {
        $expires = null;
        if (!Arrays::tryGet($response->getResponseHeaders(), 'Expires', $expires)) {
            return;
        }

        $expiresTS = Util::ensureNot(false, strtotime($expires), "Unable parse Expires value of '{$expires}'");
        $cache = array(
            '_id' => $request->getUrl(),
            'httpCode' => $response->getHttpCode(),
            'body' => $response->getResponse(),
            'headers' => $response->getResponseHeaders(),
            'expires' => new MongoDate($expiresTS),
        );
        $this->_collection->update(array('_id' => $request->getUrl()), $cache, array('upsert' => true));
    }

    /**
     * @see TOL_API_Cache_Interface::get()
     */
    public function get(TOL_API_Request $request)
    {
        $cache = $this->_collection->findOne(array('_id' => $request->getUrl()));
        if ($cache === null) {
            return null;
        }

        return new TOL_API_Response($cache['httpCode'], $cache['headers'], $cache['body']);
    }

    /**
     * Ensures proper indexes are created on the mongo cache collection
     *
     * @return void
     */
    public function ensureIndexes()
    {
        $this->_collection->ensureIndex(array('expires' => 1), array('expireAfterSeconds' => 0, 'background' => true));
    }
}
