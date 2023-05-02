<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

const QIITA_RECEIVED_CONTROLLER_PATH = "App\Http\Controllers\QiitaReceivedController";
const LINE_SEND_CONTROLLER_PATH = "App\Http\Controllers\LineSendController";

define('INSERT_SUCCESS', "レコードの登録に成功しました。【request_resend_tbl】");
define('UPDATE_SUCCESS', "レコードの更新に成功しました。【request_resend_tbl】");
define('DELETE_SUCCESS', "レコードの削除に成功しました。【request_resend_tbl】");

define('INSERT_FALSE', "レコードの登録に失敗しました。【request_resend_tbl】");
define('UPDATE_FALSE', "レコードの更新に成功失敗しました。【request_resend_tbl】");
define('DELETE_FALSE', "レコードの削除に失敗しました。【request_resend_tbl】");

define('CONDENSE_RESULT', "件すべてのレコードを削除しました。");
define('BATCH_SUCCESS', "バッチが正常に実行されました。");
define('BATCH_FALSE', "バッチが正常に実行されませんでした。");

define('QIITA_FALSE', "Qiitaの情報が正常に取得できませんでした。");
define('LINE_FALSE', "LINEのメッセージ送信に失敗しました。");

define('HOSTNAME', config('db.hostname'));
define('DATABASE', config('db.database'));
define('USERNAME', config('db.username'));
define('PASSWORD', config('db.password'));
define('PORT', config('db.port'));

define('QIITA_REQUEST_TYPE', 1);
define('LINE_REQUEST_TYPE', 2);

class LineSendApi extends Command
{

    /**
     * The name and signature of the console command.
     *　php artisan LineSendApi "keyword"
     * @var string
     */
    protected $signature = 'LineSendApi {keyword}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send "Qiita_information" to LINE';

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
     */
    public function handle()
    {    

        $keyword = $this->argument('keyword');

        // Qiita APIの呼び出し
        $qiita_controller = app()->make(QIITA_RECEIVED_CONTROLLER_PATH);
        $items = $qiita_controller->doProc($keyword);

        // LINE APIの呼び出し
        $line_controller = app()->make(LINE_SEND_CONTROLLER_PATH);
        $line_controller->doProc($items, $keyword);

        echo "\033[32m" .BATCH_SUCCESS;
        
    }
}
