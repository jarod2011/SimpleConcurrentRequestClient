<?php
/**
 * @author Jarod2011
 * @link https://github.com/jarod2011/SimpleConcurrentRequestClient
 */
namespace SimpleConcurrent;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\json_decode;
use GuzzleHttp\Cookie\CookieJarInterface;

/**
 * simple request interface
 */
interface SimpleRequestInterface
{
    /**
     * set a client implements \GuzzleHttp\ClientInterface
     * @param ClientInterface $client
     */
    public function setClient(ClientInterface $client);
    
    /**
     * get request promise implements \GuzzleHttp\Promise\PromiseInterface
     * @return PromiseInterface
     */
    public function getPromise(): PromiseInterface;
    
    /**
     * get callback function list when request successed called
     * this method will return a array and each element must be closure
     * @return \Closure[]
     */
    public function getSuccessCallbackList(): array;
    
    /**
     * get callback function list when request failed called
     * this method will return a array and each element must be closure
     * @return \Closure
     */
    public function getFailCallbackList(): array;
    
    /**
     * set response implements SimpleResponseInterface
     * @param SimpleResponseInterface $response
     */
    public function setResponse(SimpleResponseInterface $response);
    
    /**
     * get response
     * this method will return a response implements SimpleResponseInterface
     * @return SimpleResponseInterface
     */
    public function getResponse(): SimpleResponseInterface;
    
    /**
     * set the request implements \Psr\Http\Message\RequestInterface
     * @param RequestInterface $request
     * @param array $options
     */
    public function setRequest(RequestInterface $request, array $options = []);
}

/**
 * simple response interface
 */
interface SimpleResponseInterface
{
    /**
     * when request failed will use this method pass the error
     * @param mixed $error
     */
    public function setFail($error);
    
    /**
     * this method will return error when request failed
     * otherwise will return null
     * @return mixed
     */
    public function getFail();
    
    /**
     * if response failed or not
     * @return bool
     */
    public function isFail(): bool;
    
    /**
     * when request successed will use this method pass the result
     * @param mixed $result
     */
    public function setResult($result);
    
    /**
     * this method will return result when request successed
     * otherwise will return null
     * @return mixed
     */
    public function getResult();
}

/**
 * a simple request implements SimpleRequestInterface
 */
class SimpleRequest implements SimpleRequestInterface
{
    
    /**
     * request client implements \GuzzleHttp\ClientInterface
     * @var ClientInterface
     */
    private $client;
    
    /**
     * success callback list
     * every item in this array must be closure
     * @var \Closure[]
     */
    private $callbackOfSuccess = [];
    
    /**
     * fail callback list
     * every item in this array must be closure
     * @var \Closure[]
     */
    private $callbackOfFail = [];
    
    /**
     * a request promise implements \GuzzleHttp\Promise\PromiseInterface
     * @var PromiseInterface
     */
    private $promise;
    
    /**
     * the response of request
     * this response implememnts SimpleResponseInterface
     * @var SimpleResponseInterface
     */
    private $response;
    
    public function __construct()
    {
        $this->callbackOfSuccess = [];
        $this->callbackOfFail = [];
    }
    
    /**
     * get or init client
     * @return ClientInterface
     */
    private function _getClient(): ClientInterface
    {
        if (! $this->client instanceof ClientInterface) $this->client = new Client();
        return $this->client;
    }
    
    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::setRequest()
     */
    public function setRequest(RequestInterface $request, array $options = []): self
    {
        $this->promise = $this->_getClient()->sendAsync($request, $options);
        return $this;
    }
    
    public function addSuccessCallback(\Closure $callback): self
    {
        $this->callbackOfSuccess[] = $callback;
        return $this;
    }
    
    public function addFailCallback(\Closure $callback): self
    {
        $this->callbackOfFail[] = $callback;
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::getFailCallbackList()
     */
    public function getFailCallbackList(): array
    {
        return $this->callbackOfFail;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::getPromise()
     */
    public function getPromise(): PromiseInterface
    {
        if (! $this->promise) throw new RequestBuildExpection('please give a request.');
        return $this->promise;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::getSuccessCallbackList()
     */
    public function getSuccessCallbackList(): array
    {
        return $this->callbackOfSuccess;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::setClient()
     */
    public function setClient(ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }
    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::getResponse()
     */
    public function getResponse(): SimpleResponseInterface
    {
        return $this->response;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleRequestInterface::setResponse()
     */
    public function setResponse(SimpleResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }

}

class SimpleResponse implements SimpleResponseInterface
{
    private $result;
    
    private $error = null;
    
    public function __construct()
    {
        $this->error = null;
        $this->result = null;
    }
    
    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleResponseInterface::getFail()
     */
    public function getFail()
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleResponseInterface::getResult()
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleResponseInterface::isFail()
     */
    public function isFail(): bool
    {
        return $this->result === null || $this->error !== null;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleResponseInterface::setFail()
     */
    public function setFail($error)
    {
        $this->error = $error;
    }

    /**
     * {@inheritDoc}
     * @see \SimpleConcurrent\SimpleResponseInterface::setResult()
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    
}

class RequestClient
{

    /**
     * guzzle client instance
     * @var ClientInterface
     */
    private $client;
    
    /**
     * default guuzzle client config
     * @var array
     */
    private $clientConfig = [
        'timeout' => 10,
        'allow_redirects' => false,
        'cookies' => false,
        'headers' => [
            'user-agent' => 'Simple Concurrent Client v0.1'
        ]
    ];
    
    /**
     * a list of simple request instance
     * @var SimpleRequest
     */
    private $requestList;
    
    /**
     * the config of request concurrency
     * @var integer
     */
    private $configOfConcurrency = 10;
    
    private $useJsonResponse = true;
    
    public function __construct()
    {
        $this->initStatus();
    }
    
    private function _getClient(): ClientInterface
    {
        if (! $this->client instanceof ClientInterface) $this->client = new Client($this->clientConfig);
        return $this->client;
    }
    
    public function addClientHeader(string $headerName, $headerValue): self
    {
        $this->clientConfig['headers'][$headerName] = $headerValue;
        return $this;
    }
    
    public function setClientConfigOfTimeout($seconds = 10): self
    {
        $this->clientConfig['timeout'] = max(1, $seconds);
        return $this;
    }
    
    public function setEnableAllowRedirect($enable = false): self
    {
        $this->clientConfig['allow_redirects'] = boolval($enable);
        return $this;
    }
    
    public function enableCookie(): self
    {
        $this->clientConfig['cookies'] = true;
        return $this;
    }
    
    public function disableCookie(): self
    {
        $this->clientConfig['cookies'] = false;
        return $this;
    }
    
    public function setCookieInstance(CookieJarInterface $cookie)
    {
        $this->clientConfig['cookies'] = $cookie;
        return $this;
    }
    
    public function getCookieInstance()
    {
        return $this->_getClient()->getConfig('cookies');
    }
    
    public function initStatus():self
    {
        $this->requestList = [];
        $this->isPromise = false;
        return $this;
    }
    
    public function addRequest(SimpleRequest & $request): self
    {
        $this->requestList[] = $request;
        return $this;
    }
    
    private function _responseSuccessHandle($response, $index)
    {
        try {
            if (! $response instanceof ResponseInterface) throw new UnknowResponseExpection($response);
            $result = $response->getBody()->getContents();
            $cbk = $this->requestList[$index]->getSuccessCallbackList();
            if ($this->useJsonResponse) {
                array_unshift($cbk, function ($res) {
                    return json_decode($res, true);
                });
            }
            if (! empty($cbk)) {
                $result = array_reduce($cbk, function ($prev, $cb) {
                    return $cb($prev);
                }, $result);
            }
            $response = new SimpleResponse();
            $response->setResult($result);
            $this->requestList[$index]->setResponse($response);
        } catch (\Exception $e) {
            $this->_responseFailHandle($e, $index);
        }
    }
    
    private function _responseFailHandle($error, $index)
    {
        $cbk = $this->requestList[$index]->getFailCallbackList();
        if (! empty($cbk)) {
            $error = array_reduce($cbk, function ($prev, $cb) {
                return $cb($prev);
            }, $error);
        }
        $response = new SimpleResponse();
        $response->setFail($error);
        $this->requestList[$index]->setResponse($response);
    }
    
    public function responseIsJson(): self
    {
        $this->useJsonResponse = true;
        return $this;
    }
    
    public function responseIsNotJson(): self
    {
        $this->useJsonResponse = false;
        return $this;
    }
    
    /**
     * @return Generator
     */
    private function _getRequestPromise()
    {
        foreach ($this->requestList as $request) {
            yield function () use ($request) {
                return $request->getPromise();
            };
        }
    }
    
    /**
     * build a request pool implements \GuzzleHttp\Pool
     * @return Pool
     */
    private function _getRequestPool(): Pool
    {
        return new Pool($this->_getClient(), $this->_getRequestPromise(), [
            'concurrency' => max(1, $this->configOfConcurrency),
            'fulfilled' => function () {
                call_user_func_array([$this, '_responseSuccessHandle'], func_get_args());
            },
            'rejected' => function () {
                call_user_func_array([$this, '_responseFailHandle'], func_get_args());
            }
        ]);
    }
    
    /**
     * execute all request
     * @return self
     */
    public function promiseAll(): self
    {
        $pool = $this->_getRequestPool();
        $pool->promise()->wait();
        return $this;
    }
}

class RequestBuildExpection extends \Exception
{
    public function __construct($message)
    {
        parent::__construct('build request failed because ' . $message, 501);
    }
}

class UnknowResponseExpection extends \Exception
{
    public function __construct($response)
    {
        $type = 'unknow';
        if (is_string($response)) {
            $type = 'String';
        } elseif (is_array($type)) {
            $type = 'Array';
        } elseif (is_object($type)) {
            $type = 'Class ' . get_class($type);
        }
        parent::__construct('response is not a support type of ' . $type, 404);
    }
}

class ResponseReadExpection extends \Exception
{
    public function __construct()
    {
        parent::__construct('response read failed', 404);
    }
}
