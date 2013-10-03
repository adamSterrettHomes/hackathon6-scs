<?php
use DominionEnterprises\Util\Arrays;

final class FindController extends Zend_Controller_Action
{

    public function init()
    {
         $this->_helper->viewRenderer->setNoRender(true);
    }

    public function pwcsAction()
    {
        $params = $this->_request->getParams();
        $offset = Arrays::get($params, 'offset', 0);
        $limit = Arrays::get($params, 'limit', 20);
        $zipCode = Arrays::get($params, 'zip');
        $radius = Arrays::get($params, 'radius');
        $filters = ['classId' => '9404794'];
        $result = self::_getResult('cycles', $filters);
        $this->_helper->json($result);
    }

    public function atvsAction()
    {
        $filters = ['classId' => '528553'];
        $result = self::_getResult('cycles', $filters);
        $this->_helper->json($result);
    }

    private static function _getResult($resource, array $filters = [], $offset = 0, $limit = 5)
    {
        $filters += [
            'view' => 'full',
            'hasPhoto' => 'true',
            'offset' => $offset,
            'limit' => $limit,
        ];

        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');

        $cache = new TOL_API_Cache_Mongo('mongodb://localhost:27017', 'sneakyCommandoSquad', 'cache');
        $client = new TOL_API_Client(
            new TOL_API_Adapter_Curl(),
            $bootstrap->getOption('apiClientId'),
            $bootstrap->getOption('apiClientSecret'),
            $bootstrap->getOption('apiUrl'),
            $bootstrap->getOption('apiversion'),
            $cache
        );

        $getResponse = $client->index($resource, $filters);

        $collection = $getResponse->getResponse()['result'];

        $result = ['results' => []];
        foreach ($collection as $item) {
            $photo = $item['photos'][0]['urls']['140x105'];
            $result['results'][] = [
                'id' => $item['id'],
                'year' => $item['year'],
                'make' => $item['makeDisplayName'],
                'model' => $item['modelDisplayName'],
                'photo' => $photo,
                'price' => $item['price'],
                'longitude' => $item['longitude'],
                'latitude' => $item['latitude'],
                'city' => $item['city'],
                'state' => $item['state'],
            ];
        }

        return $result;
    }
}
