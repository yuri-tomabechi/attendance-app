# 勤怠アプリ

## プロジェクト概要

サービス名：coachtech勤怠管理アプリ  
サービス概要：ある企業が開発した独自の勤怠管理アプリ  
制作の背景と目的：ユーザーの勤怠と管理を目的とする  
制作の目標：初年度でのユーザー数1000人達成  
ターゲットユーザー：社会人全般  
ターゲットブラウザ・OS：PC（Chrome/Firefox/Safari　最新バージョン）  


## 主要機能
・ユーザー登録/ログイン/ログアウト  
・勤怠登録（出勤/休憩/退勤）  
・勤怠修正申請  
・勤怠修正承認（管理者）  
・勤怠一覧表示  


## 環境構築
Dockerビルド  

1. git clone  git@github.com:yuri-tomabechi/attendance-app.git  
2. docker-compose up -d --build  

*MySQLは、OSによって起動しない場合があるのでそれぞれのPCに合わせてdocker-compose.ymlファイルを編集してください。  

Laravel環境構築

1. docker-compose exec php bash  
2. composer install  
3. cp .env.example .env  .env.exampleファイルから.envを作成し、DB設定を変更（下記参照）   
4. php artisan key:generate  
5. php artisan migrate  
6. php artisan db:seed  
7. php artisan storage:link

## データベース設定(.env)
DB_CONNECTION=mysql  
DB_HOST=mysql  
DB_PORT=3306  
DB_DATABASE=attendance_app  
DB_USERNAME=laravel_user  
DB_PASSWORD=laravel_pass  

## mailhog(.env)  
MAIL_MAILER=smtp  
MAIL_HOST=mailhog  
MAIL_PORT=1025  
MAIL_USERNAME=null  
MAIL_PASSWORD=null  
MAIL_ENCRYPTION=null  
MAIL_FROM_ADDRESS=example@attendance-app.test  
MAIL_FROM_NAME="${APP_NAME}"  

## 認証について
本アプリケーションでは Laravel Fortify を使用しています。  

## ログイン情報
■ 管理者ログイン情報  
メールアドレス：admin@test.com  
パスワード：12341234  

■ 一般ユーザーログイン情報  
メールアドレス：user@test.com  
パスワード：12341234  

## ダミーデータ
php artisan migrate:fresh --seed  

・管理者1名  
・一般ユーザー複数名  
・今月分の勤怠データ（ランダム生成）  
  - 出勤・退勤時間はランダム  
  - 休憩は1〜2回  
  - 一部未退勤データ含む  

## テスト
1. cp .env .env.testing  
2. .env.testingのDBの設定を以下に変更  
    DB_CONNECTION=mysql  
    DB_HOST=mysql  
    DB_PORT=3306  
    DB_DATABASE=demo_test  
    DB_USERNAME=root  
    DB_PASSWORD=root  


## 使用技術
・フロントエンド：HTML/CSS/Blade/JavaScript  
・バックエンド： PHP/Laravel 8.83.8  
・データベース：MySQL  
・開発環境：Docker/Docker Compose  
・その他：Fortify/MailHog  


## URL
・開発環境：http://localhost  
・phpMyAdmin: http://localhost:8080/  

## ER図
![ER図](./attendance-er.png)  


