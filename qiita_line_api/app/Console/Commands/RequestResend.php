<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class RequestResend extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan RequestResend
     * @var string
     */
    protected $signature = 'RequestResend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'RequestResend "Qiita_information" to LINE';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {

        // DB接続
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );
        $dsn = 'mysql:host=' . HOSTNAME. ';dbname=' . DATABASE . ';port=' . PORT . ';';
        $pdo = new PDO($dsn, USERNAME, PASSWORD, $options); 
        
        // テーブルから再送回数が0のデータを取得する
        $selectData = $pdo -> query(config('sql.select_request_resend'))->fetchAll();

        foreach ($selectData as $requestData) {

            $id = $requestData["id"];
            $request = $requestData["request"];
            $request_type = $requestData["request_type"];

            /**
             * リクエストタイプ：1
             * Qiita API 再送
             * 
             * リクエストタイプ：2
             * LINE API 再送
             */
            if($request_type === QIITA_REQUEST_TYPE){

                // Qiita APIの呼び出し
                $qiita_controller = app()->make(QIITA_RECEIVED_CONTROLLER_PATH);
                $items = $qiita_controller->doProc($request, $id );

                if($items !== null){
                    // LINE APIの呼び出し
                    $line_controller = app()->make(LINE_SEND_CONTROLLER_PATH);
                    $line_controller->doProc($items, $request, null, $id ,$request_type);
                }

            }elseif($request_type === LINE_REQUEST_TYPE){

                // LINE APIの呼び出し
                $line_controller = app()->make(LINE_SEND_CONTROLLER_PATH);
                $line_controller->doProc(null, null, $request, $id, null);

            }

        }

        echo "\033[32m" .BATCH_SUCCESS;

    }
}
