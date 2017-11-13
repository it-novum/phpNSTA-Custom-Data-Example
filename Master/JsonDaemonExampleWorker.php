<?php

/**
 * Class Daemon
 * @author it-novum GmbH
 * @repository https://github.com/it-novum/phpNSTA-Custom-Data-Example.git
 * @license MIT <http://opensource.org/licenses/MIT>
 */

$Daemon = new Daemon(
    new GearmanWorkerTimeout(),
    new OutputModule()
);

printf(
    'Enter endless loop, press STRG+C to exit or send SIGTERM or SIGINT to my pid(%s) to stop the Daemon%s',
    getmypid(),
    PHP_EOL
);
$Daemon->run();

class Daemon {

    /**
     * @var GearmanWorkerTimeout
     */
    private $GearmanWorkerTimeout;

    /**
     * @var OutputModule
     */
    private $OutputModule;

    public function __construct(GearmanWorkerTimeout $GearmanWorkerTimeout, OutputModule $OutputModule) {
        $this->GearmanWorkerTimeout = $GearmanWorkerTimeout;
        $this->OutputModule = $OutputModule;

        //Signal handler
        pcntl_signal(SIGTERM, [$this, 'sigHandler']);
        pcntl_signal(SIGINT, [$this, 'sigHandler']);
    }

    /**
     * @param int $signo
     */
    public function sigHandler($signo) {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                exit(0);
                break;
        }
    }

    //Run the Daemon
    public function run() {
        while (true) {
            //Check for pending signals
            pcntl_signal_dispatch();

            //Get data from gearman (will timeout after 1 second)
            $data = $this->GearmanWorkerTimeout->getData();

            //Check if we have data, null on timeout
            if ($data !== null) {
                $this->OutputModule->out($data);
            }
        }
    }
}


class GearmanWorkerTimeout {

    /**
     * @var GearmanWorker
     */
    private $GearmanWorker;

    /**
     * @var null|mixed
     */
    private $data = null;


    public function __construct() {
        $this->GearmanWorker = new GearmanWorker();
        $this->GearmanWorker->addServer('127.0.0.1', 4730);

        //Wait 1 second for data
        $this->GearmanWorker->setTimeout(1000);

        //Set the queue and a callback function for processing the data
        //The callback needs to be public!
        $this->GearmanWorker->addFunction('phpnsta_custom_data', [$this, 'processData']);
    }

    /**
     * @return null|mixed
     * @throws Exception
     */
    public function getData() {
        $this->data = null;

        // work() will block until timeout is expired or there is a record in the queue
        $this->GearmanWorker->work();

        if ($this->GearmanWorker->returnCode() === GEARMAN_TIMEOUT) {
            //No jobs in queue
            return null;
        }

        if ($this->GearmanWorker->returnCode() !== GEARMAN_SUCCESS) {
            throw new Exception(sprintf('Garman return code %s !== success', $this->GearmanWorker->returnCode()));
        }

        //Return data
        if ($this->data !== null) {
            return $this->data;
        }

        return null;
    }

    /**
     * @param GearmanJob $job
     * @throws Exception
     */
    public function processData(GearmanJob $job) {
        $data = json_decode($job->workload());
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Json error: %s', json_last_error_msg());
        }

        $this->data = $data;
    }

}


class OutputModule {

    /**
     * @param mixed $data
     */
    public function out($data) {
        if (php_sapi_name() === 'cli') {
            $this->cli($data);
            return;
        }
        $this->html($data);
    }

    private function cli($data) {
        print_r($data);
        echo PHP_EOL;
    }

    private function html($data) {
        printf('<pre>%s</pre>', print_r($data, true));
    }

}
