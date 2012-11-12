<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/9/12
 * Time: 1:28 PM
 * To change this template use File | Settings | File Templates.
 */
namespace TestStackExample\Task\Couchbase\Admin\Bucket;

use TestStackExample\Task\AbstractTask;
use Processus\Serializer\XmlRpcValue;

class Dump
    extends AbstractTask
{

    /**
     * @var \SplFileInfo
     */
    private $cacheDirFileInfo;

    /**
     * @var \SplFileInfo
     */
    private $cacheDirJsonFileInfo;
    /**
     * @var \SplFileInfo
     */
    private $cacheDirXmlFileInfo;

    // php runtask.php Couchbase.Admin.Bucket.Dump


    private function init()
    {
        $this->cacheDirFileInfo = null;
        $this->cacheDirJsonFileInfo = null;
        $this->cacheDirXmlFileInfo = null;
        $cacheDir = dirname(__FILE__) . '/../../../../tmp';
        $this->cacheDirFileInfo = new \SplFileInfo($cacheDir);

    }

    /**
     * @return \SplFileInfo|null
     */
    public function getCacheDirFileInfo()
    {
        return $this->cacheDirFileInfo;
    }

    /**
     * @return \SplFileInfo|null
     */
    public function getCacheDirJsonFileInfo()
    {
        return $this->cacheDirJsonFileInfo;
    }

    /**
     * @return \SplFileInfo|null
     */
    public function getCacheDirXmlFileInfo()
    {
        return $this->cacheDirXmlFileInfo;
    }

    /**
     *
     */
    public function run()
    {

        // got good results with: memorylimit: 3000M and pageSize: 30000


        echo (
            PHP_EOL . '==========' . __METHOD__ . ' :BEGIN ===========' . PHP_EOL
        );
        echo (
            PHP_EOL . 'Recommended: MemoryLimit 3000M pageSize 30000' . PHP_EOL
        );

        $this->init();

        $client = $this->_getCouchbaseAdminClient();
        $client->setHttpClientVerbose(false);
        $host = $client->getHost();
        $restApiPort = $client->getRestApiPort();
        $username = $client->getUsername();
        $password = 'Administrator';
        $bucket = 'default';

        $recordSetPagesSize = 1000;
        // the higher the pageSize, the more moery you need
        $memoryLimit = ini_get('memory_limit');

        $host = $this->_cliPromptInput(
            'Host=' . $host . ':',
            false,
            $host
        );
        echo PHP_EOL . 'host=' . json_encode($host) . PHP_EOL;
        $restApiPort = $this->_cliPromptInput(
            'RestApiPort=' . $restApiPort . ':',
            false,
            $restApiPort
        );
        echo PHP_EOL . 'restApiPort=' . json_encode($restApiPort) . PHP_EOL;
        $username = $this->_cliPromptInput(
            'Username=' . $username . ':',
            false,
            $username
        );
        echo PHP_EOL . 'username=' . json_encode($username) . PHP_EOL;
        $password = $this->_cliPromptInput('Password:', true, $password);
        echo PHP_EOL . 'password= (secret. not displayed here)'. PHP_EOL;
        $bucket = $this->_cliPromptInput(
            'Bucket=' . $bucket . ':',
            false,
            $bucket
        );
        echo PHP_EOL . 'bucket=' . json_encode($bucket) . PHP_EOL;
        $client->setAuthEnabled($bucket !=='default');
        echo PHP_EOL . 'authEnabled='
            . json_encode($client->getAuthEnabled()) . PHP_EOL;

        $recordSetPagesSize = (int)$this->_cliPromptInput(
            'recordSetPageSize=' . $recordSetPagesSize . ':',
            false,
            $bucket
        );
        echo PHP_EOL . 'recordSetPageSize='
            . json_encode($recordSetPagesSize) . PHP_EOL;

        $memoryLimit = $this->_cliPromptInput(
            'memoryLimit=' . $memoryLimit . ':',
            false,
            $memoryLimit
        );
        ini_set('memory_limit', $memoryLimit);
        $memoryLimit = ini_get('memory_limit');
        echo PHP_EOL . 'memoryLimit=' . json_encode($memoryLimit) . PHP_EOL;

        $client->setHost($host);
        $client->setRestApiPort($restApiPort);
        $client->setUsername($username);
        $client->setPassword($password);

        $this->createFilesystemCache();

        $cacheDirJsonFileInfo = $this->getCacheDirJsonFileInfo();
        $cacheDirXmlFileInfo = $this->getCacheDirXmlFileInfo();

        $pageSize = $recordSetPagesSize;
        $offset = 0;

        $totalRows = 0;

        $params = array(
            'limit' => $pageSize,
            'skip' => $offset,
            'include_docs' => true,
        );
        $pageNo = 1;
        $rowsCounter = 0;

        $jsonFileInfo = new \SplFileInfo(
            $cacheDirJsonFileInfo->getRealPath() . '/couchbasedump.jsonx'
        );

        $xmlFileInfo = new \SplFileInfo(
            $cacheDirXmlFileInfo->getRealPath() . '/couchbasedump.xml'
        );

        echo PHP_EOL . 'Files (json) to be stored = '
            . $jsonFileInfo->getPathname() . PHP_EOL;

        echo PHP_EOL . 'Files (xml) to be stored = '
            . $xmlFileInfo->getPathname() . PHP_EOL;
        $jsonFileResource = fopen($jsonFileInfo->getPathname(), 'w+');
        $xmlFileResource = fopen($xmlFileInfo->getPathname(), 'w+');

        echo PHP_EOL . 'Calling couchbase rest api ... please wait ...'
           . PHP_EOL;

        while (true) {
            $params['limit'] = $pageSize;
            $params['skip'] = $offset;
            $data = $this->couchbaseDump($bucket, $params);

            try {
                $totalRows = $data['total_rows'];
            }catch (\Exception $e){

                /**
                 * @var $client \Processus\Couchbase\Admin\Client
                 */
                var_dump($client->getLastResponseInfo());
                throw $e;
            }

            $rows = $data['rows'];
            var_dump(
                array(
                    'totalRows' => $totalRows,
                    'rowsCount' => count($rows),
                    'pageNo' => $pageNo,
                    'params' => $params,
                    'memUsage' => memory_get_usage(false),
                    'memUsageReal' => memory_get_usage(true),
                    'memPeakUsage' => memory_get_peak_usage(false),
                    'memPeakUsageReal' => memory_get_peak_usage(true),

                )
            );

            //var_dump($rows); exit;
            if (!is_array($rows)) {
                $rows = array();
            }
            $currentRowsCount = (int)count($rows);
            if ($currentRowsCount < 1) {

                break;
            }

            foreach ($rows as $row) {
                $jsonText = json_encode($row)
                    . PHP_EOL . PHP_EOL . PHP_EOL;

                fwrite($jsonFileResource, $jsonText);

                $xmlSerializer = new XmlRpcValue();
                $xmlSerializer->setEncoding('UTF-8');
                $xmlText = $xmlSerializer->encode($row)
                    . PHP_EOL . PHP_EOL . PHP_EOL;

                fwrite($xmlFileResource, $xmlText);
            }

            $offset = $offset + (int)count($rows);
            $pageNo++;
            $rowsCounter += $currentRowsCount;
            unset($rows);
            if ($rowsCounter > $totalRows) {
                var_dump($client->getLastResponseInfo());
                throw new \Exception(
                    'RowsCounter failed. Stopped before endless loop'
                );
            }
        }

        fclose($jsonFileResource);
        fclose($xmlFileResource);

        $totalRowsCount = $totalRows;
        var_dump(
            array(
                'totalRowsCount' => $totalRowsCount,
                'json' => $jsonFileInfo->getRealPath(),
                'xml' => $xmlFileInfo->getRealPath()
            )
        );
        //var_dump($client->getLastResponseInfo());
    }

    /**
     * @throws \Exception
     */
    private function createFilesystemCache()
    {
        $cacheDirFileInfo = $this->getCacheDirFileInfo();
        //   var_dump($cacheDirFileInfo->getPathname());exit;
        if (!$cacheDirFileInfo->isDir()) {
            throw new \Exception(
                'cachedir does not exist. dir='
                    . $cacheDirFileInfo->getPathname()
                    . ' realpath=' . $cacheDirFileInfo->getRealPath()
            );
        }
        if (!$cacheDirFileInfo->isWritable()) {
            throw new \Exception(
                'cachedir not writeable. dir='
                    . $cacheDirFileInfo->getPathname()
                    . ' realpath=' . $cacheDirFileInfo->getRealPath()
            );
        }

        $host = $this->_getCouchbaseAdminClient()->getHost();

        $uuid = 'dump_' . urlencode($host)
            . '_' . str_pad(time(), 20, '0', STR_PAD_LEFT);
        $cacheDirJson = $cacheDirFileInfo->getPathname()
            . '/' . str_replace(
            array('::', '\\'),
            '_',
            strtolower(__METHOD__)
                . '_' . $uuid
                . ''
        );
        $cacheDirXml = $cacheDirFileInfo->getPathname()
            . '/' . str_replace(
            array('::', '\\'),
            '_',
            strtolower(__METHOD__)
                . '_' . $uuid
                . ''
        );
        $cacheDirJsonFileInfo = new \SplFileInfo($cacheDirJson);
        $this->cacheDirJsonFileInfo = $cacheDirJsonFileInfo;
        try {
            mkdir($cacheDirJsonFileInfo->getPathname());
        } catch (\Exception $e) {
            //nop
        }
        if (!$cacheDirJsonFileInfo->isDir()) {
            throw new \Exception(
                'cachedirjson create failed. dir='
                    . $cacheDirJsonFileInfo->getPathname()
                    . ' realpath=' . $cacheDirJsonFileInfo->getRealPath()
            );
        }

        $cacheDirXmlFileInfo = new \SplFileInfo($cacheDirXml);
        $this->cacheDirXmlFileInfo = $cacheDirXmlFileInfo;
        try {
            mkdir($cacheDirXmlFileInfo->getPathname());
        } catch (\Exception $e) {
            //nop
        }
        if (!$cacheDirXmlFileInfo->isDir()) {
            throw new \Exception(
                'cachedirxml create failed. dir='
                    . $cacheDirXmlFileInfo->getPathname()
                    . ' realpath=' . $cacheDirXmlFileInfo->getRealPath()
            );
        }
    }


    /**
     * @param $bucket
     * @param $params
     * @return array|null
     * @throws \Exception
     */
    private function couchbaseDump($bucket, $params)
    {
        $client = $this->_getCouchbaseAdminClient();

        $data = null;
        $error = null;
        try {
            $data = $client->bucketDump($bucket, $params);
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error !== null) {
            $info = $client->getLastResponseInfo();
            //var_dump($info);
            echo (
                PHP_EOL .
                    '==========' . __METHOD__ . ' :ERROR ===========' . PHP_EOL
            );

            echo $error->getMessage();

            throw new \Exception('Couchbase Error');

        }


        return $data;
    }


}
