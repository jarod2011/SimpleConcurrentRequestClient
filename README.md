SimpleConcurrentRequestClient
=================
[![GitHub license](https://img.shields.io/github/license/jarod2011/SimpleConcurrentRequestClient.svg)](https://github.com/jarod2011/SimpleConcurrentRequestClient/blob/master/LICENSE)

[SimpleConcurrentRequestClient](https://packagist.org/packages/jarod2011/simple-concurrent-request-client)是一个针对[guzzle](https://github.com/guzzle/guzzle/)做的简单封装，可以很方便简单的实现多个接口调用请求并发执行并方便的获取每个调用结果。

#### 安装
```bash
composer require jarod2011/simple-concurrent-request-client
```

#### 版本记录
版本v1.1.1 临时修复一个bug，为当前最新版本，建议更新到此版本，此版本修复了在`RequestClient`中配置的`header`、`user-agent`等参数不能在并发请求中生效，修复后，可以通过`RequestClient`中统一配置`header`中的参数了，并增加了一个`setPromise`方法，可以直接传入一个`Promise`，而若使用了此方法则setRequest方法提供的请求将无效

版本v1.1 增加了针对https是否校验的方法，以及一个自定义client配置的方法

#### 对于以下应用场景，比较适合

* 很多http接口需要调用，并且每个接口的调用对其他接口调用结果没有依赖性
* 多种三方接口调用，每种接口有不同的处理逻辑

#### 一个简单的应用场景
```php
use SimpleConcurrent\SimpleRequest;
use SimpleConcurrent\RequestClient;
use GuzzleHttp\Psr7\Request;

/* 创建一个请求并使用自动的json格式转化 */
$req1 = new SimpleRequest();
$req1->setRequest(new Request('GET', 'https://photo.home.163.com/api/designer/pc/home/index/word'))->responseIsJson();

/* 初始化客户端 */
$client = new RequestClient();

/* 传入请求 */
$client->addRequest($req1);

/* 执行传入的所有请求，当前只有一个 */
$client->promiseAll();

/* 打印结果, 将返回是一个数组 */
var_dump($req1->getResponse()->getResult());
```
#### 多个请求同时调用
```php
use SimpleConcurrent\SimpleRequest;
use SimpleConcurrent\RequestClient;
use GuzzleHttp\Psr7\Request;

/* 创建几个请求 */

/* 创建一个请求并使用自动的json格式转化 */
$req1 = new SimpleRequest();
$req1->setRequest(new Request('GET', 'https://photo.home.163.com/api/designer/pc/home/index/word'))->responseIsJson();
/* 创建一个请求但不使用自动的json格式转化 */
$req2 = new SimpleRequest();
$req2->setRequest(new Request('GET', 'https://photo.home.163.com/api/designer/pc/home/index/word'));
/* 创建一个不存在的URL请求 */
$reqError = new SimpleRequest();
$reqError->setRequest(new Request('GET', 'https://www.baidu.com/notexits'));

/* 初始化客户端 */
$client = new RequestClient();

/* 传入请求 */
$client->addRequest($req1)->addRequest($req2)->addRequest($reqError);

/* 并发调用 */
$client->promiseAll();

/* 打印结果, 请求1，将返回是一个数组 */
var_dump($req1->getResponse()->getResult());

/* 打印结果，请求2，将返回是字符串 */
var_dump($req2->getResponse()->getResult());

/* 打印结果，请求3为失败,结果为null */
var_dump($reqError->getResponse()->isFail(), $reqError->getResponse()->getResult());

/* 获取失败原因 */
$failedReason = $reqError->getResponse()->getFail();

/* 查看失败的结果类型 */

/* 客户端默认不会处理错误，将会把抛出的异常赋值给响应的错误结果 */
if (is_object($failedReason)) {
    var_dump(get_class($failedReason));
    
    /* 如果是连接错误，将可以通过getCode获取到http code */
    if ($failedReason instanceof ClientException) {
        var_dump($failedReason->getCode());
    }
} else {
    var_dump($failedReason);
}
```
