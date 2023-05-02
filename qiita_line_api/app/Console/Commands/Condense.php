<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class Condense extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan Condense
     * @var string
     */
    protected $signature = 'Condense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Request value';

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

        // プロパティテーブルから再送上限回を取得する
        $selectPropertyData = $pdo -> query(config('sql.select_property'))->fetch();
        $resend_limit = $selectPropertyData["value"];

        // テーブルから再送回数が1のデータを取得する
        $selectData = $pdo -> query(config('sql.select_condense'))->fetchAll();

        $delete_time = 0;
        foreach ($selectData as $requestData) {

            if($resend_limit <= $requestData["resend_time"] ){

                // テーブルから再送回数が1のデータを削除する
                $sql = config('sql.delete_request_resend');
                            $stmt = $pdo->prepare($sql);
                            $stmt->bindValue(':value1', $requestData["id"], PDO::PARAM_INT);
                            $stmt->execute();

                echo "\033[36m" .DELETE_SUCCESS . PHP_EOL;
                $delete_time++;
            }else{

                echo "\033[31m" .DELETE_FALSE . PHP_EOL;

            }
        }

        echo "\033[36m" .$delete_time . CONDENSE_RESULT . PHP_EOL;

        echo "\033[32m" .BATCH_SUCCESS;
    }
}
