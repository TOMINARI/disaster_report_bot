<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/vendor/autoload.php';
//include_once __DIR__.'/php/SlackBot.php';
//include_once __DIR__.'/php/SlackBotInfo.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$log = new Logger('name');
$log->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));


$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LineMessageAPIChannelAccessToken'));

$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

$sign = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

$events = $bot->parseEventRequest(file_get_contents('php://input'), $sign);

date_default_timezone_set('Asia/Tokyo');


$slack_hook_url = getenv('SlackHookURL');

$slack_dist_channel  = getenv('SlackdistChannel');


function GetUserName( $event ) {
  $uid = $event->getUserId();

   global $log;
   global $httpClient;

   $bot2 = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LineMessageAPIChannelSecret')]);

   $response = $bot2->getProfile($uid);

   $profile = $response->getJSONDecodedBody();

   $username = $profile['displayName'];

   $log->addWarning("user name ${username}\n");

   $emp =  empty( $username );

   $log->addWarning("empty  ${emp}\n");

   if ( $emp == 1 ) {
        $username = "不明";

   }

   return $username;

}


//    Slack へのPost
function  PostSlack($date, $user, $kind, $url ,$comment, $lat, $lon ) {

global $slack_hook_url;
global  $log;


if (! is_null($slack_hook_url)){
  $message = array (
    'username' => 'line_bot',

  );

  $message['text'] = "$date $user $kind $url $comment $lat $lon";


  $webhook_url = $slack_hook_url;
  $options = array(
    'http' => array(
    'method' => 'POST',
    'header' => 'Content-Type: application/json',
    'content' => json_encode($message),
    )
  );

  $log->addWarning("url ${webhook_url}\n");

  $response = file_get_contents($webhook_url, false, stream_context_create($options));

  $log->addWarning("response ${response}\n");
  return $response === 'ok';

}

else {
    return TRUE;
   }
}


function  AddAudioFileLink( $response, $event, string $filepath, string $kind, string $trtext ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

   //var_dump($event);


     //  ユーザ名の取得  debug
    $user = GetUserName($event);

    $comment = $trtext;
    $url = $filepath;
    //$comment = $event->originalContentUrl;

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );
     PostSlack($date, $user, $kind, $url ,$comment, "","");
    var_dump($resp);

     if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



}

function  GetTopSheetName( $spreadsheetID, $client ) {


  $sheets = Getsheets($spreadsheetID, $client);


  $top_sheet = $sheets[0];


}


function Getsheets($spreadsheetID, $client) {
    $sheets = array();
    // Load Google API library and set up client
    // You need to know $spreadsheetID (can be seen in the URL)


    $sheetService = new Google_Service_Sheets($client);
    $spreadSheet = $sheetService->spreadsheets->get($spreadsheetID);
    $sheets = $spreadSheet->getSheets();
    foreach($sheets as $sheet) {
        $sheets[] = $sheet->properties->sheetId;
    }
    return $sheets;
}



function  AddFileLink( $response, $event, string $filepath, string $kind ){

    $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');



    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

   //var_dump($event);


     //  ユーザ名の取得
    $user = GetUserName($event);

    $comment = "";
    $url = $filepath;
    //$comment = $event->originalContentUrl;

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );
     PostSlack($date, $user, $kind, $url ,$comment, "","");
    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



}




function AddText( $event ){
   $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');

   //$title = $event->getTitle();
   //$address = $event->getAddress();
  // $latitude = strval (  $event->getLatitude());
  // $longitude = strval ( $event->getLongitude());

    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

    //  ユーザ名の取得
   $user = GetUserName($event);
    $kind = "text";



    $url = "";
    $comment = $event->getText();

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

   // Slack へのPost
    PostSlack($date, $user, $kind, $url ,$comment, "", "" );


    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }


}




function AddLocationLink( $response, $event ){
   $spreadsheetId = getenv('SPREADSHEET_ID');

    $client = getClient();


    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setApplicationName('AddSheet');

   $title = $event->getTitle();
   $address = $event->getAddress();
   $latitude = strval (  $event->getLatitude());
   $longitude = strval ( $event->getLongitude());

    $service = new Google_Service_Sheets($client);


    $date    = date('Y/m/d H:i:s');

    //  ユーザ名の取得
   $user = GetUserName($event);
    $kind = "location";

    $url = "";
    $comment = "${title} ${address}";

     $value = new Google_Service_Sheets_ValueRange();
     $value->setValues([ 'values' => [ $date, $user, $kind, $url ,$comment, $latitude, $longitude ] ]);
     $resp = $service->spreadsheets_values->append($spreadsheetId , 'シート1!A1', $value, [ 'valueInputOption' => 'USER_ENTERED' ] );

    PostSlack($date, $user, $kind, $url ,$comment, $latitude,$longitude);
    var_dump($resp);

   if ( $user === "不明" ){
        return FALSE;
        }
    else {
        return TRUE;
        }



}






function upload_contents_gdr( $kind , $ext, $mime_type, $folder_id, $response ) {  // ファイルのGoogle Driveアップロード

          $filename = make_filename( $kind, $ext );

// Get the API client and construct the service object.
         $client = getClient_drive();
         //$client->setApplicationName(APPLICATION_NAME);




   global $log;
   $log->addWarning("file name ${filename}\n");

$fileMetadata = new Google_Service_Drive_DriveFile(array(
    'name' => $filename,
    'parents' => array($folder_id),
));

 //   'mimeType' => 'image/jpeg',

$content = $response->getRawBody();

   $log->addWarning("get Raw\n");
var_dump($fileMetadata);

$service = new Google_Service_Drive($client);

   $log->addWarning("make service \n");
   $file = $service->files->create($fileMetadata, array(
    'data' => $content,
    'mimeType' => 'image/jpeg',
    'uploadType' => 'multipart',
    'fields' => 'id'));

    $file_id = $file->getId();

    $tfileurl = "https://drive.google.com/uc?id=${file_id}";
    //$tfilename = $file->alternateLink;
    $log->addWarning("make file ${tfileurl}\n");



    return $tfileurl;

}

function make_filename( $kind, $ext ){  //  make unique file name


           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
           $filename = basename($filePath);

           return $filename;
}


function make_filename_path( $kind, $ext ){  //  make unique file name full path


           $tempFilePath = tempnam('.', "${kind}-");
           unlink($tempFilePath);
           $filePath = $tempFilePath . ".${ext}";
          // $filename = basename($filePath);

           return $filePath;
}


//  $kind   'image'  'video'  'voice'
//  $ext    'jpg'    'mp4'    'mp4'
//  $content_type  application/octet-stream

//  content upload to dropbox
function upload_contents( $kind , $ext, $content_type, $response ) {
          global $log;


          $log->addWarning("upload contents in\n");

 //          file upload


           $filename = make_filename( $kind, $ext );

            $dropboxToken = getenv('DROPBOXACCESSTOKEN');


             $url = "https://content.dropboxapi.com/2/files/upload";
             $tgfilename = "/disasterinfo/${kind}/${filename}";

             $filearg = "Dropbox-API-Arg: {\"path\":\"${tgfilename}\"}";

              $auth = "Authorization: Bearer ${dropboxToken}";
                  $headers = array(
                       $auth , //(2)
                          $filearg,//(3)
                           "Content-Type: ${content_type}"
                    );



            $log->addWarning("file name ${tgfilename}\n");


                 $options = array(
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_URL => $url,
                           CURLOPT_HTTPHEADER => $headers,
                           CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => $response->getRawBody()
                       );

                   $ch = curl_init();

                  curl_setopt_array($ch, $options);

                 $result = curl_exec($ch);

                 $log->addWarning("result ${result}\n");


                  curl_close($ch);



                 $path = createSharedLink( $tgfilename );  //
                 return $path;

}

// create shared link of dropbox content
 function createSharedLink($path)
    {
        $url = "https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings";

        $ch = curl_init();

         $dropboxToken = getenv('DROPBOXACCESSTOKEN');


        $headers = array(
            'Authorization: Bearer ' . $dropboxToken,
            'Content-Type: application/json',
        );

        $post = array(
            "path" => "{$path}", //ファイルパス
            "settings" => array(
                "requested_visibility" => array(
                    ".tag" => "public" //公開
                ),
            ),
        );

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($ch, $options);

        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $link = "";
        if (!curl_errno($ch) && $http_code == "200") {
            $res = (array)json_decode($res);
            if ($res["url"]) {
                $link = $res["url"];
            } elseif ($res["error"]) {
                //既に設定済みなど
                $error = (array)$res["error"];
                print_r("WARNING: Failed to create shared link [{$path}] - {$error['.tag']}" . PHP_EOL);
            }
        } else {
            print_r("ERROR: Failed to access DropBox via API" . PHP_EOL);
            print_r(curl_error($ch) . PHP_EOL);
        }

        curl_close($ch);

        return $link;
    }


//  Google Spread Sheet 用クライアント作成
function getClient() {


   $auth_str = getenv('authstr');

   $json = json_decode($auth_str, true);


     $client = new Google_Client();

    $client->setAuthConfig( $json );


    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);



    $client->setApplicationName('AddSheet');

    return $client;


}


define('GSCOPES', implode(' ', array(
        Google_Service_Drive::DRIVE)
));

//   Google Drive 用クライアントの作成
function getClient_drive() {

    $client = new Google_Client();

    $client->setApplicationName('upload contents');
    $client->setScopes(GSCOPES);
   $auth_str = getenv('authstr_drv');

   $json = json_decode($auth_str, true);



    $client->setAuthConfig( $json );

    $token_str = getenv('token_drv');

    $accessToken = json_decode($token_str, true);

    $client->setAccessToken($accessToken);

 // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {

        $refresh_token= getenv('token_refresh');
        $client->fetchAccessTokenWithRefreshToken( $refresh_token );

    }



    return $client;


}

//  flac オーディオファイルからテキストを取得する   debug
function getTextFromAudio( $tflc ){

       global $log;



$jsonArray = array();
$jsonArray["config"]["encoding"] = "FLAC";
$jsonArray["config"]["sampleRateHertz"] = 16000;
$jsonArray["config"]["languageCode"] = "ja-JP";
$jsonArray["config"]["enableWordTimeOffsets"] = false;

$apikey = getenv("SPEECHAPIKEY");


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://speech.googleapis.com/v1/speech:recognize?key=${apikey}");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HEADER, true);

    $jsonArray["audio"]["content"] = base64_encode(file_get_contents($tflc));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonArray));
    $response = curl_exec($curl);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    $log->addWarning("body --> ${body}\n");

   $result = json_decode($body, true);

   $transtext = $result["results"][0]["alternatives"][0]["transcript"];

   return $transtext;
}


function displayShortHelp( $bote, $evente ) {

     $helpstr = "参加ありがとうございます\n以下のコマンドをメッセージに打ち込むことができます\n\n";
     $helpstr .= "#help 利用方法表示\n";
     $helpstr .= "#map 地図表示URL表示\n";
     $helpstr .= "#list 一覧表表示URL表示\n";


      $bote->replyText($evente->getReplyToken(), $helpstr);
}


function displayHelp( $bote, $evente ) {

    $helpstr = "利用方法\n\n";
    $helpstr .= "システムの目的\n";

    $helpstr .= "LINEで皆様が投稿した位置情報,テキスト,写真,動画,音声をクラウド上のシートに保存して利用するためのシステムです。位置情報がはいっていると投稿した情報を地図で確認できます\n\n";

    $helpstr .= "グループでの利用\n";


    $helpstr .= "LINEの上で同じ情報を投稿する皆様とグループを作成して、そのグループにこのシステムを追加していただけると、他の人の投稿を見ながら投稿情報をクラウド上のシートに集めることができます\n";
    $helpstr .= "ただしグループに参加した方で災害情報収集用のチャットボットと友達になっていない方は必ずチャットボットと友達になっておいて下さい。\n";
    $helpstr .= "チャットボットと友達になっていないと投稿情報に投稿者のユーザ名が残らないので地図表示を行う場合うまく表示されなくなります。\n\n";

    $helpstr .= "位置情報の投稿\n";
    $helpstr .= "位置情報を投稿してからテキスト、写真、動画、音声を投稿してください\n";
    $helpstr .= "音声は1分間までの投稿が可能です。音声投稿は音声をテキスト化したテキストと音声データが保存されます\n";


 //    $helpstr .="位置情報投稿 line://nv/location \n";

     $helpstr .="同じ場所で連続して投稿する場合は最初に1回だけ位置情報を投稿してください。場所を変えて投稿する場合は最初に1回位置情報を投稿してください。位置情報を投稿しないと地図に表示されないか地図上のあやまった位置に表示されます。\n\n";


     $helpstr .="LINEのグループで本システムを利用する場合テキスト投稿の先頭1文字を # (半角のシャープ) で開始した投稿はスプレッドシートに保存されません。グループ内での情報共有を投稿する場合ご利用下さい\n\n";


     $helpstr .= "\n\n特殊コマンド\n";

     $helpstr .= "#map 地図表示URL表示\n";

     $helpstr .= "#list 一覧表表示URL表示\n";

      $helpstr .= "#help HELPメッセージ表示\n";
      $bote->replyText($evente->getReplyToken(), $helpstr);
}



$page = 1;
$action ="";

$score = -1;



foreach ($events as $event) {



   if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {  // Location event


        $title = $event->getTitle();
        $address = $event->getAddress();
        $latitude = $event->getLatitude();
        $longitude = $event->getLongitude();




       $tst =  AddLocationLink( $response, $event );

        if ( $tst ) {
          $bot->replyText($event->getReplyToken(), "入力位置情報 ${title} ${address} ${latitude} ${longitude}");
          }
        else
           {
                    $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n入力位置情報 ${title} ${address} ${latitude} ${longitude}");



             }
         continue;


      }




      if ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {  //  イメージメッセージの場合

            $message_id = $event->getMessageId();



            $response = $bot->getMessageContent($message_id );

            if ($response->isSucceeded()) {





                $filepath =  upload_contents( 'image' , 'jpg', 'application/octet-stream', $response );


                $tst = AddFileLink( $response, $event, $filepath, "image"  );

                if ( $tst ) {
                                $bot->replyText($event->getReplyToken(), "画像共有   ${filepath} ");

                }
                else {

                                       $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n画像共有   ${filepath} ");
                }






                continue;


				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}



            $bot->replyText($event->getReplyToken(), "イメージイベント   ${message_id} 共有失敗");


          continue;

        }



       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\AudioMessage) {  //  オーディオメッセージの場合  debug

             $message_id = $event->getMessageId();

            $response = $bot->getMessageContent($message_id );

            if ($response->isSucceeded()) {

                 $filepath =  upload_contents( 'voice' , 'mp4', 'application/octet-stream', $response );


                //  mp4 ファイルの保存
                $tmp4 = make_filename_path( "voice", "mp4" );

                $fcontents = $response->getRawBody();

                file_put_contents( $tmp4, $fcontents );


                $tflc = make_filename_path( "voice", "flac" );


                //  mp4  -> flac への変換
                shell_exec("ffmpeg -i ${tmp4} -vn -ar 16000 -ac 1 -acodec flac -f flac ${tflc}");

                //  mp4 ファイルの削除

                unlink( $tmp4 );

                //  flac ファイルのテキスト変換

                $voicetext = getTextFromAudio( $tflc );


                unlink( $tflc );

                $tst =  AddAudioFileLink( $response, $event, $filepath, "voice" ,${voicetext} );


                if ( $tst ) {
                $bot->replyText($event->getReplyToken(), "音声共有   ${filepath} ${voicetext}");
                  }
                else  {
                    $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\n音声共有   ${filepath} ${voicetext}");

                }



                continue;


				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}





            $bot->replyText($event->getReplyToken(), "音声イベント   共有エラー");


          continue;

        }


       if ($event instanceof \LINE\LINEBot\Event\MessageEvent\VideoMessage) {  //  ビデオメッセージの場合


             $message_id = $event->getMessageId();

            $response = $bot->getMessageContent($message_id );

            if ($response->isSucceeded()) {

                 $filepath =  upload_contents( 'video' , 'mp4', 'application/octet-stream', $response );


                 $tst =  AddFileLink( $response, $event, $filepath, "video"  );

                 if ( $tst ) {
                     $bot->replyText($event->getReplyToken(), "ビデオ共有   ${filepath} ");
                     }
                  else {
                     $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nビデオ共有   ${filepath} ");

                  }

                continue;


				} else {
  					  error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
			}





            $bot->replyText($event->getReplyToken(), "ビデオイベント   共有エラー");


          continue;



        }

    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\FileMessage) {  //  ファイルメッセージの場合

             $message_id = $event->getMessageId();

            $response = $bot->getMessageContent($message_id );
            
            $fname = $response->file_name;
            
            $fpath = pathinfo($fname);
            
            $ext = $fpath['extension'];
            
                  


          $log->addWarning("file name   ${fname}\n");
          $log->addWarning("extention  ${ext}\n");        

           $filepath =  upload_contents( 'file' , $ext, 'application/octet-stream', $response );



            $tst = AddFileLink( $response, $event, $filepath, "file"  );


            if ( $tst ) {
            $bot->replyText($event->getReplyToken(), "ファイルイベント   line://nv/location ");
              }
            else  {

                $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nファイルイベント   line://nv/location ");
            }

          continue;

        }


   if ($event instanceof \LINE\LINEBot\Event\JoinEvent) {  // Join event add


    $log->addWarning("join event!\n");
   //$bot->replyText($event->getReplyToken(), "友達追加ありがとうございます");
   displayShortHelp( $bot, $event );

     //  firstmessage( $bot, $event,0);
       continue;

   }


  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent) ||
      !($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {

      if (!($event instanceof \LINE\LINEBot\Event\PostbackEvent) ) {

          displayShortHelp( $bot, $event );
        // $bot->replyText($event->getReplyToken(), " なんかのイベント発生");

             continue;
      }
     else  {

       $bot->replyText($event->getReplyToken(), "post back event");
         continue;
        }

      }



     if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {  //  テキストメッセージの場合
            $tgText=$event->getText();


           //  テキスト１文字目が # の場合はコメントとみなしてスキップする  20190621

           $chktext  = substr( $tgText, 0, 1 );


           if ( strcmp($chktext, "#" ) == 0 ) {


                   $spreadsheetId = getenv('SPREADSHEET_ID');

                    if ( strcmp($tgText, "#map" ) == 0 ) {   //  display map URL

                       $bot->replyText($event->getReplyToken(), "地図表示     https://reportmap.herokuapp.com/?sheetid=${spreadsheetId}");   //map urL
                       }

                   if ( strcmp($tgText, "#sheet" ) == 0 ) {   //  display sheet URL

                       $bot->replyText($event->getReplyToken(), "集計シート (閲覧)     https://docs.google.com/spreadsheets/d/${spreadsheetId}/edit?usp=sharing");   //sheet urL
                       }
                  if ( strcmp($tgText, "#list" ) == 0 ) {   //  display sheet URL

                       $bot->replyText($event->getReplyToken(), "集計シート (閲覧)     https://docs.google.com/spreadsheets/d/${spreadsheetId}/edit?usp=sharing");   //sheet urL
                       }



                    if ( strcmp($tgText, "#help" ) == 0 ) {   //  display help
                         displayHelp( $bot, $event );

                       }


                   continue;
                   }

            $tst = AddText(  $event  );

            if ( $tst ) {



                $bot->replyText($event->getReplyToken(), "テキストメッセージ    ${tgText}");
                }
             else {

                             $bot->replyText($event->getReplyToken(), "【警告】LINE Botと友達になっていないのでユーザ名が取得できません。\n位置情報が正しく記録できないのでLINE Botと友達になって下さい。\nテキストメッセージ    ${tgText}");
             }


          continue;

        }





        $bot->replyText($event->getReplyToken(), "その他メッセージ   ");

   }
