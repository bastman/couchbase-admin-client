<?php
/**
 * Created by JetBrains PhpStorm.
 * User: seb
 * Date: 11/2/12
 * Time: 3:06 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Processus\Couchbase\Admin;

class Client
{

    /**
     * @var int
     */
    protected $_httpConnectTimeout = 5;

    /**
     * @var int
     */
    protected $_httpClientVerbose = false;

    /**
     * @var array|null
     */
    protected $_lastResponseInfo;

    /**
     * @var string
     */
    protected $_username;
    /**
     * @var string
     */
    protected $_password;

    /**
     * @var string
     */
    protected $_host = 'localhost';

    /**
     * @var int
     */
    protected $_port = 8091;


    /**
     * @var int
     */
    protected $_restApiPort = 8092;

    /**
     * @var bool
     */
    protected $_authEnabled = true;

    /**
     * @param string $value
     * @return Client
     */
    public function setUsername($value)
    {
        $this->_username = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return (string)$this->_username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return (string)$this->_password;
    }

    /**
     * @param string $value
     * @return Client
     */
    public function setPassword($value)
    {
        $this->_password = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return (string)$this->_host;
    }

    /**
     * @param string $value
     * @return Client
     */
    public function setHost($value)
    {
        $this->_host = $value;

        return $this;
    }


    /**
     * @return int
     */
    public function getPort()
    {
        return (int)$this->_port;
    }

    /**
     * @param int $value
     * @return Client
     */
    public function setPort($value)
    {
        $this->_port = (int)$value;

        return $this;
    }


    /**
     * @return int
     */
    public function getRestApiPort()
    {
        return (int)$this->_restApiPort;
    }

    /**
     * @param int $value
     * @return Client
     */
    public function setRestApiPort($value)
    {
        $this->_restApiPort = (int)$value;

        return $this;
    }

    /**
     * @return int
     */
    public function getHttpConnectTimeout()
    {
        return (int)$this->_httpConnectTimeout;
    }

    /**
     * @param int $value
     * @return Client
     */
    public function setHttpConnectTimeout($value)
    {
        $this->_httpConnectTimeout = (int)$value;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHttpClientVerbose()
    {
        return ($this->_httpClientVerbose===true);
    }

    /**
     * @param bool $value
     * @return Client
     */
    public function setHttpClientVerbose($value)
    {
        $this->_httpClientVerbose = ($value===true);

        return $this;
    }



    /**
     * @return bool
     */
    public function getAuthEnabled()
    {
        return ($this->_authEnabled===true);
    }

    /**
     * @param bool $value
     * @return Client
     */
    public function setAuthEnabled($value)
    {
        $this->_authEnabled = ($value===true);

        return $this;
    }
    /**
     * @param $bucket
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function bucketDump($bucket, $params=array())
    {
        //  http://localhost:8092/gamesim-sample/_all_docs?include_docs=true

        if(!is_array($params)) {
            $params = array();
        }

        $paramsDefault = array(
            'stale'=>false,
            'reduce'=>false,
            'include_docs'=>true,
        );
        foreach($paramsDefault as $key => $value) {
            if(!array_key_exists($key, $params)) {
                $params[$key] = $value;
            }
        }


        $params[
            'processusnocache'] = time() . '_' . rand(10000, 1000000)
        ;

        $username = $this->getUsername();
        $password = $this->getPassword();


        $uriPath = '/'.$bucket.'/_all_docs';

        $apiUrl = $this->newCouchbaseRestApiUrl($uriPath, array());

        $responseInfo = $this->curlGetBasicAuth(
            $apiUrl,
            $params,
            $username,
            $password
        );

        $responseBodyData = $responseInfo['bodyData'];

        if (!is_array($responseBodyData)) {

            throw new \Exception(
                'Invalid responseData! ' . __METHOD__ . get_class($this)
            );
        }

        return $responseBodyData;


    }

    private function newCouchbaseRestApiUrl(
        $uriPath, array $params = array()
    )
    {


        $couchbaseHost = $this->getHost();
        $restApiPort = $this->getRestApiPort();

        $uriPath = (string)trim((string)$uriPath);
        if(strpos($uriPath ,'/' ,0) !==0) {
            $uriPath .= '/';
        }

        $url = 'http://'
            . $couchbaseHost
            . ':' . $restApiPort
            . $uriPath;


        if (!is_array($params)) {
            $params = array();
        }

        // Prepare query string
        $query = array();
        foreach ($params as $key => $value) {
            if (isset($value)) {
                if (is_array($value) || is_bool($value)) {
                    $value = json_encode($value);
                }
                $query[] = "$key=$value";
            }
        }
        $queryString = implode('&', $query);

        $urlFinal = (string)$url;

        if(count(array_keys($query))>0) {
           $urlFinal .= '?' . $queryString;
        }

        return $urlFinal;

    }

    /**
     * @param string $bucket
     * @return array
     * @throws \Exception
     */
    public function bucketDocumentsList($bucket)
    {
        $host = $this->getHost();
        $port = $this->getPort();

        $url    = 'http://' . $host . ':' . $port
            . '/pools/default/buckets/' . $bucket . '/ddocs';
        $params = array(
            'processusnocache' => time() . '_' . rand(10000, 1000000),
        );

        $username = $this->getUsername();
        $password = $this->getPassword();

        $responseInfo = $this->curlGetBasicAuth(
            $url,
            $params,
            $username,
            $password
        );

        $responseBodyData = $responseInfo['bodyData'];

        if (!is_array($responseBodyData)) {

            throw new \Exception(
                'Invalid responseData! ' . __METHOD__ . get_class($this)
            );
        }

        return $responseBodyData;
    }

    /**
     * @param string $bucket
     * @param string $design
     * @return array
     * @throws \Exception
     */
    public function bucketDesignViewsList($bucket, $design)
    {
        $host = $this->getHost();
        $port = $this->getPort();

        $url    = 'http://' . $host . ':' . $port
            . '/couchBase/' . $bucket . '/_design/'.$design;
        $params = array(
            'processusnocache' => time() . '_' . rand(10000, 1000000),
        );

        $username = $this->getUsername();
        $password = $this->getPassword();

        $responseInfo = $this->curlGetBasicAuth(
            $url,
            $params,
            $username,
            $password
        );

        $responseBodyData = $responseInfo['bodyData'];

        if (!is_array($responseBodyData)) {

            throw new \Exception(
                'Invalid responseData! ' . __METHOD__ . get_class($this)
            );
        }

        return $responseBodyData;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function bucketsList()
    {
        $host = $this->getHost();
        $port = $this->getPort();

        $url    = 'http://' . $host . ':' . $port . '/pools/default/buckets';
        $params = array(
            'processusnocache' => time() . '_' . rand(10000, 1000000),
        );

        $username = $this->getUsername();
        $password = $this->getPassword();

        $responseInfo = $this->curlGetBasicAuth(
            $url,
            $params,
            $username,
            $password
        );

        $responseBodyData = $responseInfo['bodyData'];

        if (!is_array($responseBodyData)) {

            throw new \Exception(
                'Invalid responseData! ' . __METHOD__ . get_class($this)
            );
        }

        return $responseBodyData;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function tasksList()
    {
        $host = $this->getHost();
        $port = $this->getPort();

        $url    = 'http://' . $host . ':' . $port . '/pools/default/tasks';
        $params = array(
            'processusnocache' => time() . '_' . rand(10000, 1000000),
        );

        $username = $this->getUsername();
        $password = $this->getPassword();

        $responseInfo = $this->curlGetBasicAuth(
            $url,
            $params,
            $username,
            $password
        );

        $responseBodyData = $responseInfo['bodyData'];

        if (!is_array($responseBodyData)) {

            throw new \Exception(
                'Invalid responseData! ' . __METHOD__ . get_class($this)
            );
        }

        return $responseBodyData;
    }


    /**
     * @param $url
     * @param array $params
     * @param $username
     * @param $password
     * @return array
     */
    public function curlGetBasicAuth($url, $params, $username, $password)
    {
        $this->_lastResponseInfo = null;

        $url = (string)$url;

        if (!is_array($params)) {
            $params = array();
        }

        // Prepare query string
        $queryString = array();
        foreach ($params as $key => $value) {
            if (isset($value)) {
                if (is_array($value) || is_bool($value)) {
                    $value = json_encode($value);
                }
                $queryString[] = "$key=$value";
            }
        }
        $queryString = implode('&', $queryString);

        $urlFinal = (string)$url . '?' . $queryString;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlFinal);
        curl_setopt(
            $ch,
            CURLOPT_CONNECTTIMEOUT,
            (int)$this->getHttpConnectTimeout()
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if($this->getAuthEnabled()){
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, '' . $username . ':' . $password);
        }
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_setopt($ch, CURLOPT_VERBOSE, $this->getHttpClientVerbose()); // set to true for debugging
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $responseText = (string)curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = (string)substr((string)$responseText, 0, $header_size);
        $body        = (string)substr((string)$responseText, $header_size);

        curl_close($ch);

        $responseInfo = array(
            'url'          => $url,
            'params'       => $params,
            'requestUrl'   => $urlFinal,
            'responseText' => (string)$responseText,
            'headerText'   => $header,
            'bodyText'     => $body,
            'bodyData'     => json_decode($body, true),
        );

        $this->_lastResponseInfo = $responseInfo;

        return $responseInfo;
    }

    /**
     * @return array|null
     */
    public function getLastResponseInfo()
    {
        return $this->_lastResponseInfo;
    }

}
