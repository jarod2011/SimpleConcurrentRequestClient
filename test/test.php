<?php
require realpath(dirname(__DIR__)) . '/vendor/autoload.php';

use SimpleConcurrent\SimpleRequest;
use SimpleConcurrent\RequestClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

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