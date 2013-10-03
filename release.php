#!/usr/bin/env php
<?php
// Define application environment
 defined('APPLICATION_ENV')
     || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
use DominionEnterprises\Util;
try {

    $returnStatus = null;
    passthru('composer install --dev', $returnStatus);
    if ($returnStatus !== 0) {
        exit(1);
    }

    require_once 'vendor/autoload.php';

    $config = (new Zend_Config_Ini('application/configs/application.ini', APPLICATION_ENV))->toArray();

    $client = new TOL_API_Client(
        new TOL_API_Adapter_Curl(),
        $config['mediaApiClientId'],
        $config['mediaApiClientSecret'],
        $config['mediaApiUrl'],
        $config['mediaApiversion']
    );

    $directory = dir('./public/js');

    for ($entry = $directory->read(); $entry !== false; $entry = $directory->read()) {
        if (pathinfo($entry, PATHINFO_EXTENSION) !== 'js')
            continue;

        $path = realpath("./public/js/{$entry}");
        $basename = pathinfo($path, PATHINFO_FILENAME);

        $getResponse = $client->get('media', $basename);

        if ($getResponse->getHttpCode() === 200) {
            echo "Updating {$path} in media solution\n";
            $id = $getResponse->getResponse()['ids'][1];
            $putResponse = $client->put('media', $id, ['media' => base64_encode(file_get_contents($path))]);
            if ($putResponse->getHttpCode() !== 204) {
                echo "Unable to update {$path}\n";
                print_r($putResponse->getResponseHeaders());
                die(print_r($putResponse->getResponse(), 1));
            }
        } elseif ($getResponse->getHttpCode() === 404) {
            $media = [
                'media' => base64_encode(file_get_contents($path)),
                'metadata' => ['project' => 'hackathon'],
                'mimeType' => 'application/javascript',
                'ids' => [$basename],
            ];

            $postResponse = $client->post('media', $media);

            if ($postResponse->getHttpCode() !== 201) {
                echo "Unable to update {$path}\n";
                die(print_r($postResponse->getResponse(), 1));
            }
        } else {
            echo "Unable to fetch from media api\n";
            print_r($getResponse->getResponseHeaders());
            die(print_r($getResponse->getResponse(), 1));
        }
    }

} catch (Exception $e) {
    fwrite(STDERR, "Release failed. {$e->getMessage()}\n");
    exit(1);
}
