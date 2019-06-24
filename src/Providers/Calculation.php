<?php
namespace LapayGroup\RussianPost\Providers;

use GuzzleHttp\Psr7\Response;
use LapayGroup\RussianPost\Exceptions\RussianPostException;
use LapayGroup\RussianPost\Singleton;

class Calculation
{
    use Singleton;

    private $httpClient;
    const VERSION = 'v1';

    function __construct($timeout = 60)
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri'=>'https://tariff.pochta.ru/tariff/'.self::VERSION.'/',
            'timeout' => $timeout,
            'http_errors' => false
        ]);
    }

    /**
     * Инициализирует вызов к API
     *
     * @param $method
     * @param $params
     * @return array
     * @throws RussianPostException
     */
    private function callApi($type, $method, $params = [])
    {
        switch ($type) {
            case 'GET':
                $response = $this->httpClient->get($method, ['query' => $params]);
                break;
            case 'POST':
                $response = $this->httpClient->post($method, ['json' => $params]);
                break;
        }

        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 404 && $response->getStatusCode() != 400)
            throw new RussianPostException('Неверный код ответа от сервера Почты России при вызове метода '.$method.': ' . $response->getStatusCode(), $response->getStatusCode(), $response->getBody()->getContents());

        $resp = json_decode($response->getBody()->getContents(), true);

        if (empty($resp))
            throw new RussianPostException('От сервера Почты России при вызове метода '.$method.' пришел пустой ответ', $response->getStatusCode(), $response->getBody()->getContents());

        if ($response->getStatusCode() == 404 && !empty($resp['code']))
            throw new RussianPostException('От сервера Почты России при вызове метода '.$method.' получена ошибка: '.$resp['sub-code'] . " (".$resp['code'].")", $response->getStatusCode(), $response->getBody()->getContents());

        if ($response->getStatusCode() == 400 && !empty($resp['error']))
            throw new RussianPostException('От сервера Почты России при вызове метода '.$method.' получена ошибка: '.$resp['error'] . " (".$resp['status'].")", $response->getStatusCode(), $response->getBody()->getContents());

        return $resp;
    }
    /**
     * Получение списка категорий
     *
     * @return mixed
     * @throws RussianPostException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCategoryList()
    {
        $params = [
            'jsontext' => true,
            'category' => 'all'
        ];

        return $this->callApi('GET', 'dictionary', $params);
    }

    /**
     * Описание категории
     *
     * @param $category_id
     * @return mixed
     * @throws RussianPostException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCategoryDescription($category_id)
    {
        $params = [
            'jsontext' => true,
            'category' => $category_id
        ];

        return $this->callApi('GET', 'dictionary', $params);
    }

    /**
     * Расчет тарифа
     *
     * @param $object_id
     * @param $params
     * @param $services
     * @return mixed
     * @throws RussianPostException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTariff($object_id, $params, $services)
    {
        $params['object'] = $object_id;
        $params['jsontext'] = true;
        if(!empty($services))
            $params['service'] = implode(',', $services);

        return $this->callApi('GET', 'calculate', $params);
    }

    /**
     * Описание объекта
     *
     * @param $object_id
     * @return mixed
     * @throws RussianPostException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getObjectInfo($object_id)
    {
        $params = [
            'jsontext' => true,
            'object' => $object_id
        ];

        return $this->callApi('GET', 'dictionary', $params);
    }

    /**
     * Список стран
     *
     * @return array
     * @throws RussianPostException
     */
    public function getCountryList()
    {
        $params = [
            'json' => true,
            'country' => false
        ];

        $result = $this->callApi('GET', 'dictionary', $params);

        return !empty($result['country']) ? $result['country'] : [];
    }
}