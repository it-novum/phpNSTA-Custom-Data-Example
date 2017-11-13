<?php

/**
 * Class JsonExampleClient
 * @author it-novum GmbH
 * @repository https://github.com/it-novum/phpNSTA-Custom-Data-Example.git
 * @license MIT <http://opensource.org/licenses/MIT>
 */

$ExampleClient = new JsonExampleClient();
$ExampleClient->addData();
echo 'Json data added to queue' . PHP_EOL;

class JsonExampleClient {

    /**
     * @var GearmanClient
     */
    private $GearmanClient;

    public function __construct() {
        $this->GearmanClient = new GearmanClient();

        //Add a timeout of 10 seconds
        $this->GearmanClient->setTimeout(10000);
        $this->GearmanClient->addServer('127.0.0.1', 4730);
    }

    public function addData() {
        $data = [
            'source'  => gethostname(),
            'time'    => date('d.m.Y H:i:s'),
            'layer_1' => [
                'layer_2' => [
                    'data'
                ]
            ]
        ];

        $this->GearmanClient->doBackground('phpnsta_custom_input', json_encode($data));
    }
}

