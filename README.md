# phpNSTA Custom Data Example
This repository contains some PHP example code, to transfer custom data from an
openITCOCKPIT Satellite to your Master system through phpNSTA.

## Basic concept
First of all be warned, if possible you should always use tools like `rsync` or `scp`.


How ever, in some cases this is not possible, but you need to transfer non monitoring data
from your openITCOCKPIT Satellite to your Master server.


On the Satellite system, you can put custom data to the Gearman queue `phpnsta_custom_input`
using a [GearmanClient](http://php.net/manual/de/class.gearmanclient.php). You need to code the
GearmanClient script by yourself.


All records will be picked up by `phpNSTAClient` and pushed to the queue `phpnsta_custom_bulk`.


`phpNSTA` on your Master system will fetch all records from the remote queue `phpnsta_custom_bulk`
and store them into the local queue `phpnsta_custom_data`.


The local data can be accessed and processed via a
[GearmanWorker](http://php.net/manual/de/class.gearmanworker.php). Also the GearmanWorker needs to be
developed by yourself.


You can find examples of how to develop an own GearmanClient and GearmanWorker in this repository.


So you have full control over all external APIs and phpNSTA will just ship your data.

## Satellite usage (Transmitter)
### 1. Json Data Example
In this example, we are going to transfer a JSON string from an Satellite System to the Master system.


**Add a new JSON record**
````
$ php Satellite/JsonExampleClient.php
Json data added to queue
````
This record will now be transferred by `phpNSTA` to your Master system.

### 2. Binary data (PNG image)
It is also possible to transmit binary data. Please keep in mind that the Gearman queues are stored
in memory. If you restart the Gearman-Job-Server, all data in the queues get lost.
In addition large files will use a lot of memory.


**Add binary data to queue**
````
$ php Satellite/ImageExampleClient.php
Image added to queue
````

## Master usage (Receiver)
### 1. Json Data Example

**Run the JSON Example GearmanWorker**
````
$ php Master/JsonExampleWorker.php
Enter endless loop, press STRG+C to exit
````

The example GearmanWorker will run as an endless loop, to simulate a daemon.
Whenever a new JSON object gets received, the object will be printed to the terminal.
````
stdClass Object
(
    [source] => staging-nightly-sat
    [time] => 13.11.2017 13:35:32
    [layer_1] => stdClass Object
        (
            [layer_2] => Array
                (
                    [0] => data
                )
        )
)
````

If you put non JSON data into the queue, you will get an `Syntax error`

### 2. Binary data (PNG image)
````
$ php Master/ImageExampleWorker.php
Enter endless loop, press STRG+C to exit
````

Whenever the GearmanWorker receives a new image, it will print the following message:
````
Image saved to /tmp/example_output.png
````

Due to the fact, that it's a basic example, there is no check, if the received data is really an image.

### 3. JSON Example GearmanWorker - as Daemon
Most of the time, you need to run your GearmanWorker as a daemon in the background.

You can take a look at this example to see how to get your data, implementing signal handling, etc...

````
$ php Master/JsonDaemonExampleWorker.php
Enter endless loop, press STRG+C to exit or send SIGTERM or SIGINT to my pid(17464) to stop the Daemon
````

Also the daemon will print all received data to the terminal
````
stdClass Object
(
    [source] => staging-nightly-sat
    [time] => 13.11.2017 17:11:18
    [layer_1] => stdClass Object
        (
            [layer_2] => Array
                (
                    [0] => data
                )

        )

)
````

You can use this example configuration, to start the daemon through `systemd`
````
[Unit]
Description=JSON Example GearmanWorker as Daemon
After=syslog.target network.target

[Service]
Type=simple
Restart=on-failure
ExecStart=/usr/local/bin/Master/JsonDaemonExampleWorker.php
RestartSec=30

[Install]
WantedBy=multi-user.target
````

## Data transport
Every custom data transmission will be logged to the log file `/var/log/phpNSTA.log` (if enabled).
````
13.11.2017 17:23:53 =>     Child [28292 -> staging-nightly-sat.oitc.itn (127.0.0.1:55307)]: Received 29 custom data records
````


Custom data will not be compressed!


All custom data records will be base64 encoded by phpNSTAClient and decoded by phpNSTA on the Master system.


Even if phpNSTA itself tries to transmit data as bulk packages, you will get single jobs
again on the Master (queue: `phpnsta_custom_data`). So data transmission is 100% transparent.



# License (MIT)
Copyright 2017 it-novum GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.