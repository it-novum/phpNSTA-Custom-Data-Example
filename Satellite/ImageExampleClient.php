<?php

/**
 * Class ImageExampleClient
 * @author it-novum GmbH
 * @repository https://github.com/it-novum/phpNSTA-Custom-Data-Example.git
 * @license MIT <http://opensource.org/licenses/MIT>
 */

$ExampleClient = new ImageExampleClient();
$ExampleClient->addImage();
echo 'Image added to queue' . PHP_EOL;

class ImageExampleClient {

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

    public function addImage() {
        $sourceImage = sprintf('%s/example.png', __DIR__);
        if (!file_exists($sourceImage)) {
            throw new Exception(sprintf('Source image %s not found', $sourceImage));
        }

        $this->GearmanClient->doBackground('phpnsta_custom_input', file_get_contents($sourceImage));
    }
}

