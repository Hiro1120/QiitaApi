<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Exception;
use PDO;

const ARTICLES_PAGE = 10;
const QIITA_RECEIVE_RESURT = "Qiitaの情報が正常に取得できました。";
const INSERT_QIITA_REQUEST_TYPE = 1;
const INSERT_QIITA_RESEND_TIME = 0;

class QiitaReceivedController extends Controller{

    public function doProc($keyword, $id = null) {

        // Qiita APIエンドポイントURL
        $url = config('qiita.url');

        // Qiita APIトークン
        $token = config('qiita.token');

        // リクエストパラメータ
        $params = array(
            "query" => $keyword,         // 検索するキーワード
            "per_page" => ARTICLES_PAGE  // 取得する記事数
        );

        // リクエストヘッダー
        $headers = array(AUTHORIZATION . $token);

        // curlセッションを初期化
        $curl = curl_init();

        // curlオプションを設定
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . "?" . http_build_query($params), // リクエストURL
            CURLOPT_RETURNTRANSFER => true, // curl_exec()の結果を文字列で返す
            CURLOPT_HTTPHEADER => $headers, // リクエストヘッダー
            CURLOPT_SSL_VERIFYPEER => false // SSL証明書の検証を行わない
        ));

        try{
            // curlセッションを実行
            $response = curl_exec($curl);

            // curlセッションをクローズ
            curl_close($curl);

            if ($response === false) {
                // curl_execが失敗した場合の例外処理
                throw new Exception("curl_exec failed" . PHP_EOL);
            }

            // レスポンスをJSONデコード
            $items = json_decode($response, true);
        
            if (json_last_error() !== JSON_ERROR_NONE) {
                // JSONデコードが失敗した場合の例外処理
                throw new Exception("json_decode failed" . PHP_EOL);
            }

            echo "\033[36m" .QIITA_RECEIVE_RESURT . PHP_EOL;

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

            return $items;

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
            * Qiita情報初回取得失敗　$id === null　再送回数：0で登録
            * Qiita情報再取得失敗　$id === not null　再送回数：1で更新
            */
            if($id === null){

                try{
                    // 初回Qiita取得失敗メッセージ
                    echo "\033[31m" . QIITA_FALSE . $e->getMessage();

                    // テーブルにデータを挿入する
                    $sql = config('sql.insert_request_resend');
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':value1', INSERT_QIITA_REQUEST_TYPE, PDO::PARAM_INT);
                    $stmt->bindValue(':value2', $keyword, PDO::PARAM_STR);
                    $stmt->bindValue(':value3', INSERT_QIITA_RESEND_TIME, PDO::PARAM_INT);
                    $stmt->execute();

                    echo "\033[36m" .INSERT_SUCCESS . PHP_EOL;

                    //処理終了
                    exit;

                }catch(Exception $e){

                    echo "\033[31m" . INSERT_FALSE . $e->getMessage() . PHP_EOL;
    
                    //処理終了
                    exit;
                }

            }else{

                try{
                    // Qiita情報再取得失敗メッセージ
                    echo "\033[31m" ."【再取得失敗】" . QIITA_FALSE . $e->getMessage();

                    // 再送回数を更新する
                    $sql = config('sql.update_request_resend');
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':value1', $id, PDO::PARAM_INT);
                    $stmt->execute();

                    echo "\033[36m" .UPDATE_SUCCESS . PHP_EOL;

                    return;

                }catch(Exception $e){

                    echo "\033[31m" . UPDATE_FALSE . $e->getMessage() . PHP_EOL;
    
                    //処理終了
                    exit;
                }

            }
        }
    }
}
?>