# 画像投稿可能な掲示板

## 概要

テキスト投稿機能、画像アップロード機能を実装しています。\
投稿内容はMyAQLに保存されており、投稿日時やIDが自動で付与されます。\
１ページに１０件まで表示され投稿が増え続けるとページ数も自動で増えていきます。


### PCからAWS上のEC2へログイン
```
ssh ec2-user@<EC2のパブリックID> -i C:<鍵ファイルのパス>
```
### screen起動
```
screen
```
### docker compose
まず作業用ディレクトリ作成、その中に移動
```
mkdir dockertest
cd dockertest
```
設定ファイルを書く
```
vim compose.yml
```
中身
````
services:
  web:
    image: nginx:latest
    ports:
    - 80:80
```
起動
```
docker compose up
```

起動できたらブラウザで確認

### データベース作成
MySQLに接続
```
docker compose exec mysql -u root
```

### データベースを作成
```
CREATE DATABASE example_db;
USE example_db;
```

### 掲示板用テーブルを作成
```
CREATE TABLE bbs_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    body TEXT NOT NULL,
    image_filename TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
```

