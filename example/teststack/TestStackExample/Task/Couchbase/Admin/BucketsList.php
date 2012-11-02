<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 10/25/12
 * Time: 6:51 PM
 * To change this template use File | Settings | File Templates.
 */

namespace TestStackExample\Task\Couchbase\Admin;

use TestStackExample\Task\AbstractTask;

class BucketsList
    extends AbstractTask
{

    // php runtask.php Couchbase.Admin.BucketsList

    /**
     *
     */
    public function run()
    {
        echo (
            PHP_EOL . '==========' . __METHOD__ . ' :BEGIN ===========' . PHP_EOL
        );

        $client   = $this->_getCouchbaseAdminClient();
        $host     = $client->getHost();
        $port     = $client->getPort();
        $username = $client->getUsername();

        $host     = $this->_cliPromptInput(
            'Host=' . $host . ':',
            false,
            $host
        );
        $port     = $this->_cliPromptInput(
            'Port=' . $port . ':',
            false,
            $port
        );
        $username = $this->_cliPromptInput(
            'Username=' . $username . ':',
            false,
            $username
        );
        $password = $this->_cliPromptInput('Password:', true, '');

        $client->setHost($host);
        $client->setPort($port);
        $client->setUsername($username);
        $client->setPassword($password);

        $data  = null;
        $error = null;
        try {
            $data = $client->bucketsList();
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error !== null) {
            $info = $client->getLastResponseInfo();
            var_dump($info);
            echo (
                PHP_EOL .
                    '==========' . __METHOD__ . ' :ERROR ===========' . PHP_EOL
            );

            echo $error->getMessage();
        } else {
            echo (
                PHP_EOL
                    . '==========' . __METHOD__ . ' :RESULT ===========' . PHP_EOL
            );
            var_dump($data);
        }


        echo (
            PHP_EOL . '==========' . __METHOD__ . ' :END ===========' . PHP_EOL
        );
    }


}
