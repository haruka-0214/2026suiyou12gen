# 画像投稿可能な掲示板

## 概要

テキスト投稿機能、画像アップロード機能を実装しています。\
投稿内容はMyAQLに保存されており、投稿日時やIDが自動で付与されます。\
１ページに１０件まで表示され投稿が増え続けるとページ数も自動で増えていきます。


### PCからAWS上のEC2へログイン
```
ssh ec2-user@<EC2のパブリックID> -i C:<鍵ファイルのパス>
```
### docker compose
まず作業用ディレクトリ作成、その中に移動
```
mkdir dockertest
cd dockertest
```
screen起動
```
screen
```

設定ファイルを書く
```
vim compose.yml
```
中身
```
services:
  web:
    image: nginx:latest
    ports:
    - 80:80

mysql:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD:root
    ports:
      - 3306:3306
```

起動
```
docker compose up
```

起動できたらブラウザで確認

### MySQLデータベース作成
MySQLに接続
```
docker compose exec mysql mysql -u root
```

データベースを作成
```
CREATE DATABASE example_db;
USE example_db;
```

掲示板用テーブルを作成
```
CREATE TABLE bbs_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    body TEXT NOT NULL,
    image_filename TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
```
docker compose upを再起動して確認。


## nginx

設定ファイル用のディレクトリ作成
```
mkdir nginx
mkdir nginx/conf.d
```
設定ファイル作成
```
vim nginx/conf.d/default.conf
```
中身
```
server {
    listen       0.0.0.0:80;
    server_name  _;
    charset      utf-8;

    root /var/www/public;
}
```
配信するファイルを置くディレクトリとファイル作成
```
mkdir public
vim public/index.html
```
中身
```
<!DOCTYPE html>
<h1>Hello world</h1>
```
compose.ymlを編集
```
services:
  web:
    image: nginx:latest
    ports:
        - 80:80
    volumes:
        - ./nginx/conf.d/:/etc/nginx/conf.d/
        - ./public/:/var/www/public/
```

dockercomposeを再起動して確認
