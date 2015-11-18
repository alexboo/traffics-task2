<?php

/**
 * Created by PhpStorm.
 * User: alexboo
 * Date: 18.11.15
 * Time: 20:50
 */
class Console
{
    /**
     * Список переданных в консоль параметров
     * @var array
     */
    private $params = [];

    public function __construct(array $argv = [])
    {
        $this->parseParams($argv);
    }

    /**
     * Парсит параметры и добавляет их в массив параметров
     * @param array $argv
     */
    public function parseParams(array $argv = [])
    {
        if (count($argv) > 1) {
            $argv = array_slice($argv, 1);
            foreach ($argv as $value) {
                $param = explode('=', ltrim($value, '--'));
                if (count($param) == 2) {
                    list($key, $value) = $param;
                    $this->params[$key] = $value;
                }
            }
        }
    }

    /**
     * Получить параметр по ключу
     * @param $key
     * @return mixed
     */
    public function getParam($key)
    {
        if ($this->hasParam($key))
        {
            return $this->params[$key];
        }
    }

    /**
     * Получить все параметры
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Проверяет передан ли указанный параметр
     * @param $key
     * @return bool
     */
    public function hasParam($key)
    {
        return isset($this->params[$key]);
    }

    /**
     * Проверяет переданы ли вообще параметры
     * @return bool
     */
    public function hasParams()
    {
        return !empty($this->params);
    }
}