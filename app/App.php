<?php

/**
 * Created by PhpStorm.
 * User: alexboo
 * Date: 18.11.15
 * Time: 21:06
 */
class App
{
    const MAX_POPULAR_VIDEO_IN_CATEGORY = 100;

    /**
     * @var Db
     */
    protected $db;

    /**Запуст приложения
     * @param Console $console
     * @param Db $db
     */
    public function run(Console $console, Db $db)
    {
        $this->db = $db;

        if (!$console->hasParams()) {
            $this->showHelp();
        } else {
            foreach ($console->getParams() as $command => $value) {
                if (method_exists($this, $command)) {
                    $this->{$command}($value);
                }
            }
        }
    }

    /**
     * Ставит лайк на видео
     * @param $id
     * @throws Exception
     */
    public function like($id)
    {
        $video = $this->getVideo($id);

        $video['likes']++;
        $this->db->query('UPDATE video SET likes = :likes WHERE id = :id', ['id' => $id, 'likes' => $video['likes']]);
        $balls = $this->getBalls($video['likes'], $video['dislikes']);

        $this->setPopularVideo($video['id'], $video['categories'], $balls);
    }

    /**
     * Ставит дизлайк на видео
     * @param $id
     * @throws Exception
     */
    public function dislike($id)
    {
        $video = $this->getVideo($id);

        $video['dislikes']++;
        $this->db->query('UPDATE video SET dislikes = :dislikes WHERE id = :id', ['id' => $id, 'dislikes' => $video['dislikes']]);
        $balls = $this->getBalls($video['likes'], $video['dislikes']);

        $this->setPopularVideo($video['id'], $video['categories'], $balls);
    }

    /**
     * Выводит рекомендции для видео
     * @param $id
     * @throws Exception
     */
    public function advice($id)
    {
        $video = $this->getVideo($id);

        $rows = $this->db->fetchAll("SELECT * FROM video_popular WHERE category_id IN(" . $video['categories'] . ") ORDER BY balls DESC LIMIT 50");

        $videos = [];
        if (!empty($rows)) {
            shuffle($rows);
            $videosIds = array_slice(array_map(function($row) {return $row['video_id'];}, $rows), 0, 5);

            $videos = $this->db->fetchAll("SELECT * FROM video WHERE id IN(" . implode(',', $videosIds) . ")");
        }

        $this->output(print_r($videos, true));
    }

    /**
     * Записывает видео в топ саммых папулярных видео если оно подходит по балам или видео в топе не привышает MAX_POPULAR_VIDEO_IN_CATEGORY
     * @param $videoId
     * @param $categoriesIds
     * @param $balls
     * @throws Exception
     */
    public function setPopularVideo($videoId, $categoriesIds, $balls)
    {
        if (!empty($categoriesIds)) {
            $categories = explode(',', $categoriesIds);
            foreach ($categories as $category) {
                // Если видео есть в списке популярных то в нем просто обновляются баллы
                $this->db->query("UPDATE video_popular SET balls = :balls WHERE video_id = :videoId AND category_id = :categoryId", [
                    'videoId' => $videoId,
                    'categoryId' => $category,
                    'balls' => $balls
                    ]);

                if ($this->db->getConnect()->affected_rows < 1) {
                    // если нет видео, получается видео с самым меньшим колличеством баллов
                    $minBallsVideo = $this->db->fetchRow("SELECT SQL_CALC_FOUND_ROWS * FROM video_popular WHERE category_id = :categoryId ORDER BY balls ASC LIMIT 1", ['categoryId' => $category]);
                    $foundRows = $this->db->fetchOne("SELECT FOUND_ROWS();");
                    $insertVideo = false;
                    // если популярных видео в категории меньше чем значение это значит что мы можем просто добавить видео в популярные
                    if ($foundRows < self::MAX_POPULAR_VIDEO_IN_CATEGORY) {
                        $insertVideo = true;
                    } else if ($minBallsVideo['balls'] < $balls) {
                        // если у текущего видео балл выше чем у видео с наименьшим балом, то заменяем видео с наименьшим балом на текущее.
                        $insertVideo = true;
                        $this->db->query("DELETE FROM video_popular WHERE video_id = :videoId AND category_id = :categoryId", [
                            'videoId' => $minBallsVideo['video_id'],
                            'categoryId' => $minBallsVideo['category_id']
                        ]);
                    }

                    if ($insertVideo) {
                        $this->db->query("INSERT INTO video_popular (video_id, category_id, balls) VALUES (:videoId, :categoryId, :balls)", [
                            'videoId' => $videoId,
                            'categoryId' => $category,
                            'balls' => $balls
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Загружает видео из базы данных по ID
     * @param $id
     * @return array|null
     * @throws Exception
     */
    protected function getVideo($id)
    {
        $video = $this->db->fetchRow("SELECT * FROM video WHERE id = :id LIMIT 1", ['id' => $id]);

        if (empty($video)) {
            throw new Exception("Not found video");
        }

        return $video;
    }

    // Нижняя граница доверительного интервала Вильсона (Wilson) для параметра Бернулли
    protected function getBalls($likes, $dislikes)
    {
        if (empty($likes)) {
            return -$dislikes;
        }
        $n = $likes + $dislikes;
        $z = 1.64485;
        $phat = $likes / $n;

        return round((($phat + $z * $z / (2 * $n) - $z * sqrt(($phat * (1 - $phat) + $z * $z / (4 * $n)) / $n)) / (1 + $z * $z / $n))*1000);
    }

    /**
     * Pfgecr vbuhfwbb
     * @param $action
     */
    protected function migration($action)
    {
        new Migration($this->db, $this, $action);
    }

    /**
     * Выводит помощ по приложению
     */
    protected function showHelp()
    {
        $commands = [
            "Доступные для cli команды",
            "--like=ID лайк на видео",
            "--dislike=ID дизлайк на видео",
            "--advice=ID рекомендации для видео",
            "--migration=run запуск миграции",
            "--migration=generate генерация данных"
        ];

        $this->output(implode("\n", $commands));
    }

    /**
     * Вывод строки в консоль
     * @param $string
     */
    protected function output($string)
    {
        echo date('Y-m-d H:i:s') . ' - ' . $string . PHP_EOL;
    }
}