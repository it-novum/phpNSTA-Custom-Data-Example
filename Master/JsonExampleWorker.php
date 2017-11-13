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
        $this->GearmanWorker->addFunction('phpnsta_custom_data', [$this, 'processData']);
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
    public function processData(GearmanJob $job) {
        $data = json_decode($job->workload());
        if (json_last_error() !== JSON_ERROR_NONE) {
            $OutputModule = new OutputModule(json_last_error_msg());
            $OutputModule->out();
            return;
        }

        $OutputModule = new OutputModule($data);
        $OutputModule->out();
    }

}

class OutputModule {

    /**
     * @var mixed
     */
    private $data;

    /**
     * OutputModule constructor.
     * @param mixed $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    public function out() {
        if (php_sapi_name() === 'cli') {
            $this->cli();
            return;
        }
        $this->html();
    }

    private function cli() {
        print_r($this->data);
        echo PHP_EOL;
    }

    private function html() {
        printf('<pre>%s</pre>', print_r($this->data, true));
    }

}
