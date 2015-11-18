<?php

/**
 * Created by PhpStorm.
 * User: alexboo
 * Date: 18.11.15
 * Time: 21:45
 */
class Migration
{
    /**
     * @var Db
     */
    protected $db;
    /**
     * @var App
     */
    protected $app;

    public function __construct(Db $db, App $app, $action)
    {
        $this->db = $db;

        $this->app = $app;

        if (method_exists($this, $action)) {
            $this->{$action}();
        }
    }

    /**
     * Создает нужные таблицы в базе данных
     */
    public function run()
    {
        $this->crateTables();
    }

    /**
     * Генерирует данные в базе данных
     */
    protected function generate()
    {
        $videos = rand(10, 10);

        for ($i = 0; $i < $videos; $i ++) {
            $this->addVideo();
        }
    }

    /**
     * Генерирует случайное виео и вставляет его в базу данных
     * @throws Exception
     */
    protected function addVideo()
    {
        $categories = [1,2,3,4,5,6,7,8,9,10];
        shuffle($categories);

        $categoriesIds = implode(',', array_slice($categories, 0, rand(1,3)));

        $name = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);

        $this->db->query("INSERT INTO video (name, categories) VALUES (:name, :categories)", ['name' => $name, 'categories' => $categoriesIds]);

        $videoId = $this->db->lastInsertId();

        for ($i = 0; $i <= rand(10, 100); $i ++) {
            $this->app->like($videoId);
        }

        for ($i = 0; $i <= rand(10, 100); $i ++) {
            $this->app->dislike($videoId);
        }
    }

    protected function crateTables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `video` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NULL,
	`categories` VARCHAR(100) NULL,
	`likes` INT UNSIGNED NULL,
	`dislikes` INT UNSIGNED NULL,
	`views` INT UNSIGNED NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `video_popular` (
	`video_id` INT UNSIGNED NOT NULL,
	`category_id` INT UNSIGNED NOT NULL,
	`balls` INT DEFAULT 0,
	PRIMARY KEY (`video_id`, `category_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;");
    }
}