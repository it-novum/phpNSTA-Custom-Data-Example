<?php

/**
 * Class JsonExampleWorker
 * @author it-novum GmbH
 * @repository https://github.com/it-novum/phpNSTA-Custom-Data-Example.git
 * @license MIT <http://opensource.org/licenses/MIT>
 */

$ExampleWorker = new JsonExampleWorker();

echo 'Enter endless loop, press STRG+C to exit' . PHP_EOL;
$ExampleWorker->loop();

class JsonExampleWorker {

    /**
     * @var GearmanWorker
     */
    private $GearmanWorker;

    public function __construct() {
        $this->GearmanWorker = new GearmanWorker();
        $this->GearmanWorker->addServer('127.0.0.1', 4730);

        //Set the queue and a callback function for processing the data
        //The callback needs to be public!
        $this->GearmanWorker->addFunction('phpnsta_custom_data', [$this, 'saveImage']);
    }

    public function loop() {
        while (true) {
            // work() will block until there are records in the queue
            $this->GearmanWorker->work();
        }
    }

    /**
     * @param GearmanJob $job
     */
    public function saveImage(GearmanJob $job) {
        $image = $job->workload();
        $filename = '/tmp/example_output.png';

        $file = fopen($filename, 'w+');
        fwrite($file, $image);
        fclose($file);

        printf('Image saved to %s%s', $filename, PHP_EOL);

    }

}
