<?php

/**
 * Created by PhpStorm.
 * User: alexboo
 * Date: 18.11.15
 * Time: 20:48
 */
class Db
{
    /**
     * Соединение с базой данных
     * @var mysqli|null
     */
    private $_connect = null;

    public function __construct($host, $user, $password, $database)
    {
        $this->_connect = new mysqli($host, $user, $password, $database);
    }

    /**
     * Отправляет запрос в базу данных
     * @param $query
     * @param null $bind
     * @return bool|mysqli_result
     * @throws Exception
     */
    public function query($query, $bind = null)
    {
        $query = $this->escapeQuery($query, $bind);

        if ($results = $this->_connect->query($query))
            return $results;
        else {
            throw new Exception($this->_connect->error);
        }
    }

    /**
     * Экранирет переданный параметр
     * @param $value
     * @return string
     */
    public function quote($value)
    {
        return "'" . $this->_connect->escape_string($value) . "'";
    }

    /**
     * Получает первое значение из запрса
     * @param $query
     * @param null $bind
     * @return null
     * @throws Exception
     */
    public function fetchOne($query, $bind = null)
    {
        if ($results = $this->query($query, $bind)) {

            $data = $results->fetch_row();

            if (null !== $data) {
                $data = each($data);

                return $data['value'];
            }
        }

        return null;
    }

    /**
     * Получет первую строку из запроса
     * @param $query
     * @param null $bind
     * @return array|null
     * @throws Exception
     */
    public function fetchRow($query, $bind = null)
    {
        if ($results = $this->query($query, $bind)) {

            return $results->fetch_assoc();
        }

        return null;
    }

    /**
     * Получает все строки из запроса
     * @param $query
     * @param null $bind
     * @return array|null
     * @throws Exception
     */
    public function fetchAll($query, $bind = null)
    {
        if ($results = $this->query($query, $bind)) {

            while ($row = $results->fetch_assoc()) {
                $data[] = $row;
            }

            return (isset($data) ? $data : null);
        }

        return null;
    }

    /**
     * Возвращает id из последнего инсерта
     * @return mixed
     */
    public function lastInsertId()
    {
        return $this->_connect->insert_id;
    }

    /**
     * Биндинд переданные параметры в запрос
     * @param null $query
     * @param array|null $bind
     * @return mixed|null
     */
    public function escapeQuery($query = null, array $bind = null)
    {
        if (null != $bind) {

            foreach ($bind as $key => $value) {
                $query = str_replace(":$key", $this->quote($value), $query);
            }
        }

        return $query;
    }

    public function getConnect()
    {
        return $this->_connect;
    }
}