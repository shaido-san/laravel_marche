## 通販サイトづくり

## ダウンロード方法

git clone https://github.com/shaido-san/laravel_marche.git

git clone ブランチを指定してダウンロードする場合
git clone -b ブランチ名 https://github.com/shaido-san/laravel_marche.git


## インストール後の実施事項

画像のダミーデータは
public/imagesフォルダ内に
sample1.jpg ~ sample6.jpg として
保存している。

php artisan storage:linkで
storageフォルダにリンク後、

storage/app/public/productsフォルダ内に
保存すると表示される。
(productsフォルダがない場合は作成する。)

ショップの画像を表示する場合は、
storage/app/public/shopsフォルダを作成し
画像を保存する。