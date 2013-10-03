<?php
use DominionEnterprises\Util\Arrays;

final class FindController extends Zend_Controller_Action
{

    public function init()
    {
         $this->_helper->viewRenderer->setNoRender(true);
    }

    public function trailersAction()
    {
        $cycleResult = self::_getResult('cycles', $this->_request->getParams(), ['classId' => '3284663']);
        $truckResult = self::_getResult('trucks', $this->_request->getParams(), ['classId' => '1']);
        $equipResult = self::_getResult('equipment', $this->_request->getParams(), ['classId' => '2356642']);
        $result = array_merge($cycleResult['results'], $truckResult['results'], $equipResult['results']);
        shuffle($result);
        $this->_helper->json(['result' => $result]);
    }

    public function rvsAction()
    {
        $result = self::_getResult('rvs', $this->_request->getParams());
        $this->_helper->json($result);
    }

    public function trucksAction()
    {
        $result = self::_getResult('trucks', $this->_request->getParams(), ['classId' => ['1', '2', '3', '4', '5', '6', '7', '8']]);
        $this->_helper->json($result);
    }

    public function equipmentAction()
    {
        $result = self::_getResult('equipment', $this->_request->getParams());
        $this->_helper->json($result);
    }

    public function cyclesAction()
    {
        $result = self::_getResult('cycles', $this->_request->getParams(), ['classId' => '356953']);
        $this->_helper->json($result);
    }

    public function pwcsAction()
    {
        $result = self::_getResult('cycles', $this->_request->getParams(), ['classId' => '9404794']);
        $this->_helper->json($result);
    }

    public function atvsAction()
    {
        $result = self::_getResult('cycles', $this->_request->getParams(), ['classId' => '528553']);
        $this->_helper->json($result);
    }

    public function snowmobilesAction()
    {
        $result = self::_getResult('cycles', $this->_request->getParams(), ['classId' => '301857']);
        $this->_helper->json($result);
    }

    private static function _getResult($resource, array $parameters, array $filters = [])
    {
        $filters += [
            'view' => 'full',
            'hasPhoto' => 'true',
            'offset' => Arrays::get($parameters, 'offset', 0),
            'limit' => Arrays::get($parameters, 'limit', 20),
        ];

        $zip = Arrays::get($parameters, 'zip');
        if ($zip !== null)
            $filters['zip'] = $zip;

        $radius = Arrays::get($parameters, 'radius');
        if ($radius !== null)
            $filters['radius'] = $radius;

        $longitude = Arrays::get($parameters, 'longitude');
        if ($longitude !== null)
            $filters['longitude'] = $longitude;

        $latitude = Arrays::get($parameters, 'latitude');
        if ($latitude !== null)
            $filters['latitude'] = $latitude;

        $sellerType = Arrays::get($parameters, 'sellerType');
        if ($sellerType !== null)
            $filters['sellerType'] = $sellerType;

        $dealerId = Arrays::get($parameters, 'dealerId');
        if ($dealerId !== null)
            $filters['dealerId'] = $dealerId;

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
                'zip' => $item['zip'],
                'link' => $item['adDetailUrl'],
                'sellerType' => $item['dealerId'] === null ? 'private' : 'dealer',
            ];
        }

        return $result;
    }
}
