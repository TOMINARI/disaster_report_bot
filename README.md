# disaster_report_bot
災害情報レポートボット

先遣隊システム

### 動作時の環境変数

| 環境変数名 | 内容 | 備考 |
|:---|:---|:---|
|APPLICATION_NAME |アプリケーション名　|DropBoxフォルダ名に利用 |
|authstr |Google Spread　Sheet アクセス認証用　JSON | 参考はこちら。https://dream-yt.github.io/post/spreadsheet-via-service-account/|
|DROPBOXACCESSTOKEN　|DropBoxアクセストークン | https://flashair-developers.github.io/website/docs/tutorials/lua/6.html|
|LineMessageAPIChannelAccessToken |LINEメッセージングアプリ アクセストークン ||
|LineMessageAPIChannelSecret |LINEメッセージングアプリ チャンネルシークレット ||
|MapURL |地図表示モジュールURL |login module のredirect URIにも利用 |
|SPEECHAPIKEY |Google Speech API　Key |設定が無い場合でもOK その場合音声文字変換は行わない |
|SPREADSHEET_ID |Google Spread Sheet　ID ||
|CLIENT_ID |LINE login module  channel ID |設定が無い場合認証は行われない|
|CLIENT_SECRET |LINE login module channel secret |設定が無い場合認証は行われない|


### 改訂履歴


- 20200613   地図モジュールと一体化　（地図のURLはこのシステムをいれた場所のベースURLになる）.地図モジュールを利用する場合LINEのログインをいれることができるようにした.書き込み用シートを先頭のシートにした（従来は　シート1　という名前のシート）.ただし　config という名前のシートが先頭の場合は2番目のシート.　　　　　　


- 20200507　　DropBoxのバイナリデータ保存ホルダをアプリケーション別に分けた


- 20190503   Line bot で送信   テキスト  写真   ボイスメッセージ   

- 20190518   サーバ側 暫定でDropBox に格納   Lineで送信した場合写真に位置情報がつかない
