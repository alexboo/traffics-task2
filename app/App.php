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

    public function like($id)
    {
        $video = $this->getVideo($id);

        $video['likes']++;
        $this->db->query('UPDATE video SET likes = :likes WHERE id = :id', ['id' => $id, 'likes' => $video['likes']]);
        $balls = $this->getBalls($video['likes'], $video['dislikes']);

        $this->setPopularVideo($video['id'], $video['categories'], $balls);
    }

    public function dislike($id)
    {
        $video = $this->getVideo($id);

        $video['dislikes']++;
        $this->db->query('UPDATE video SET dislikes = :dislikes WHERE id = :id', ['id' => $id, 'dislikes' => $video['dislikes']]);
        $balls = $this->getBalls($video['likes'], $video['dislikes']);

        $this->setPopularVideo($video['id'], $video['categories'], $balls);
    }

    public function advice($id)
    {
        $video = $this->getVideo($id);

        $rows = $this->db->fetchAll("SELECT * FROM video_popular WHERE category_id IN(" . $video['categories'] . ") ORDER BY balls DESC LIMIT 50");

        $videos = [];
        if (!empty($rows)) {
            shuffle($rows);
            $videosIds = array_slice(array_map(function($row) {return $row['video_id'];}, $rows), 5);

            $videos = $this->db->fetchAll("SELECT * FROM video WHERE id IN(" . implode(',', $videosIds) . ")");
        }

        $this->output(print_r($videos, true));
    }

    public function setPopularVideo($videoId, $categoriesIds, $balls)
    {
        $this->db->fetchAll("SELECT ");
    }

    protected function getVideo($id)
    {
        $video = $this->db->fetchRow("SELECT * FROM video WHERE id = :id", ['id' => $id]);

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

    protected function migration($action)
    {
        new Migration($this->db, $this, $action);
    }

    protected function showHelp()
    {
        $this->output("--like=ID лайк на видео, --dislike=ID дизлайк на видео, --advice=ID рекомендации для видео --migration=run запуск миграции --migration=generate генерация данных");
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