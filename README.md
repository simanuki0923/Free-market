# Free-market

## 環境構築
- Free-marketホーム画面 http://localhost/
- 会員登録画面　http://localhost/register
- phpMyAdmin http://localhost:8080/

### Dockerビルド
- git clone https://github.com/simanuki0923/Free-market.git
- cd Free-market
- docker compose up -d --build

### Laravel環境構築
- docker-compose exec php bash
- composer install
- cp .env.local .env
- php artisan key:generate
- php artisan migrate
- php artisan db:seed

- .env設定設定変更
- SESSION_DRIVER=file
- QUEUE_CONNECTION=sync
- CACHE_STORE=file

- MAIL_STREAM_ALLOW_SELF_SIGNED=true
- MAIL_STREAM_VERIFY_PEER=false

## 使用技術
- docker
- Laravel 12.X
- PHP 8.x
- mysql 8.4
- nginx 1.28
- fortify1.30
- stripe15.8

## ER図
![alt text](img/ER図.png)



