<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
use PDO;

const MESSAGE_TYPE = "text";
const CONTENT_TYPE = "Content-Type: application/json";
const LINE_SEND_RESURT = "LINEのメッセージが正常に送信されました。";
const INSERT_LINE_REQUEST_TYPE = 2;
const INSERT_LINE_RESEND_TIME = 0;

class LineSendController extends Controller{
    
    public function doProc($items, $resend_params = null, $id = null, $request_type = null) {

        // LINE APIエンドポイントURL
        $url = config('line.url');

        // LINE APIトークン
        $token = config('line.token');


        if($id === null || $request_type === QIITA_REQUEST_TYPE){
            //メッセージの整形（記事のタイトルとリンク）
            $message_array = null;
            $i = 1;
            foreach ($items as $item) {
                $message_array .= $i++ . ". 【".$item["title"]."】" . PHP_EOL .$item["url"]  . PHP_EOL . PHP_EOL;
            }

            // LINE Messaging APIでブロードキャスト配信
            $messageData = [
                'type' => MESSAGE_TYPE,
                'text' => $message_array,
            ];

            // リクエストパラメータ
            $params = array(
                "messages" => [$messageData], // 配信するメッセージ
            );
            $params = json_encode($params, true);

        }else{

            $params = $resend_params;
            
        }

        // リクエストヘッダー
        $headers = array(
            AUTHORIZATION . $token,
            CONTENT_TYPE,
        );

        // curlセッションを初期化
        $curl = curl_init();

        // // curlオプションを設定
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        try{
            // curlセッションを実行
            //$response = curl_exec($curl);
            $response = false;
            // curlセッションをクローズ
            curl_close($curl);

            if ($response === false) {
                // curl_execが失敗した場合の例外処理
                throw new Exception("curl_exec failed" . PHP_EOL);
            }

            echo "\033[36m" . LINE_SEND_RESURT . PHP_EOL;

            if($id !== null){

                // DB接続
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                );
                $dsn = 'mysql:host=' . HOSTNAME. ';dbname=' . DATABASE . ';port=' . PORT . ';';
                $pdo = new PDO($dsn, USERNAME, PASSWORD, $options);

                // 再送回数を更新する
                $sql = config('sql.update_request_resend');
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':value1', $id, PDO::PARAM_INT);
                $stmt->execute();

                echo "\033[36m" .UPDATE_SUCCESS . PHP_EOL;

            }

        }catch(Exception $e){

            // DB接続
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            );
            $dsn = 'mysql:host=' . HOSTNAME. ';dbname=' . DATABASE . ';port=' . PORT . ';';
            $pdo = new PDO($dsn, USERNAME, PASSWORD, $options);

            /**
            * LINE初回送信失敗　$id === null　再送回数：0で登録
            * LINE再送失敗　$id === not null　再送回数：1で更新
            */
            if($id === null || $request_type === QIITA_REQUEST_TYPE){

                try{

                    echo "\033[31m" . LINE_FALSE . $e->getMessage();
            
                    // テーブルにデータを挿入する
                    $sql = config('sql.insert_request_resend');
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':value1', INSERT_LINE_REQUEST_TYPE, PDO::PARAM_INT);
                    $stmt->bindValue(':value2', $params, PDO::PARAM_STR);
                    $stmt->bindValue(':value3', INSERT_LINE_RESEND_TIME, PDO::PARAM_INT);
                    $stmt->execute();

                    echo "\033[36m" .INSERT_SUCCESS . PHP_EOL;

                }catch(Exception $e){

                    echo "\033[31m" . INSERT_FALSE . $e->getMessage() . PHP_EOL;

                }

            }else{

                try{

                    echo "\033[31m" . "【再送失敗】" . LINE_FALSE . $e->getMessage();

                    // テーブルにデータを挿入する
                    $sql = config('sql.update_request_resend');
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':value1', $id, PDO::PARAM_INT);
                    $stmt->execute();

                    echo "\033[36m" . UPDATE_SUCCESS . PHP_EOL;     

                }catch(Exception $e){

                    echo "\033[31m" . UPDATE_FALSE . $e->getMessage() . PHP_EOL;

                }

            }
        }

    }
}
?>