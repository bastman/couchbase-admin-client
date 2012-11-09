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
        echo (
            PHP_EOL . '==========' . __METHOD__ . ' :BEGIN ===========' . PHP_EOL
        );

        $this->init();


        $client = $this->_getCouchbaseAdminClient();
        $client->setHttpClientVerbose(false);
        $host = $client->getHost();
        $restApiPort = $client->getRestApiPort();
        $username = $client->getUsername();
        $password = 'Administrator';
        $bucket = 'default';


        $host = $this->_cliPromptInput(
            'Host=' . $host . ':',
            false,
            $host
        );

        $restApiPort = $this->_cliPromptInput(
            'RestApiPort=' . $restApiPort . ':',
            false,
            $restApiPort
        );
        $username = $this->_cliPromptInput(
            'Username=' . $username . ':',
            false,
            $username
        );
        $password = $this->_cliPromptInput('Password:', true, $password);
        $bucket = $this->_cliPromptInput(
            'Bucket=' . $bucket . ':',
            false,
            $bucket
        );
        $client->setAuthEnabled($bucket !=='default');

        $client->setHost($host);
        $client->setRestApiPort($restApiPort);
        $client->setUsername($username);
        $client->setPassword($password);


        $this->createFilesystemCache();


        $cacheDirJsonFileInfo = $this->getCacheDirJsonFileInfo();
        $cacheDirXmlFileInfo = $this->getCacheDirXmlFileInfo();


        $pageSize = 1000;
        $offset = 0;

        $totalRows = 0;
        // $allRows = array();


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

        $jsonFileResource = fopen($jsonFileInfo->getPathname(), 'w+');
        $xmlFileResource = fopen($xmlFileInfo->getPathname(), 'w+');

        while (true) {
            $params['limit'] = $pageSize;
            $params['skip'] = $offset;
            $data = $this->couchbaseDump($bucket, $params);

            try {
                $totalRows = $data['total_rows'];
            }catch (\Exception $e){

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
                    'memUsage'=>memory_get_usage(true),
                    'memPeakUsage'=>memory_get_peak_usage(true),

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
                //$allRows[] = $rows;
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
        // $allRowsCount = count($allRows);
        var_dump(
            array(
                'totalRowsCount' => $totalRowsCount,
                'json' => $jsonFileInfo->getRealPath(),
                'xml' => $xmlFileInfo->getRealPath()
            )
        );
        //var_dump($client->getLastResponseInfo());


    }


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

        $uuid = str_pad(time(), 20, '0');
        $cacheDirJson = $cacheDirFileInfo->getPathname()
            . '/' . str_replace(
            array('::', '\\'),
            '_',
            strtolower(__METHOD__)
                . '_' . $uuid
                . '_json'
        );
        $cacheDirXml = $cacheDirFileInfo->getPathname()
            . '/' . str_replace(
            array('::', '\\'),
            '_',
            strtolower(__METHOD__)
                . '_' . $uuid
                . '_xml'
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
