<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-07-20 19:42:20
 * @LastEditTime: 2021-09-15 10:23:52
 * @Description: Forward, no stop
 */
namespace app\push\controller;

use app\common\controller\PushController;
use think\facade\Db;
use app\common\service\ElasticService;
use app\common\service\HuobiRedis;
use app\common\service\KlineService;
class Huobi extends PushController
{
    // 交易对列表，由 Events.php 设置
    public static $symbols = [];

    public static function elastic()
    {
        $elastic = new ElasticService();
        return $elastic;
    }
    public static function hbrds()
    {
        $setredis = \think\facade\Config::get('cache.stores.redis');
        $hbrds = new HuobiRedis($setredis['host'], $setredis['port'], $setredis['password']);
        return $hbrds;
    }
    public static function debugLog($msg)
    {
        $logFile = '/tmp/binance_debug.log';
        $time = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$time}] [Huobi] {$msg}\n", FILE_APPEND);
    }

    /**
     * 连接成功后分批订阅频道，避免一次性发送过多导致HTX断连
     */
    public static function onAsyncConnect($con)
    {
        self::debugLog("onAsyncConnect - subscribing to " . count(self::$symbols) . " symbols (batched)...");

        // 构建所有订阅消息
        $subs = [];
        foreach (self::$symbols as $symbol) {
            $symbol = strtolower(trim($symbol));
            $subs[] = ['sub' => "market.{$symbol}.ticker", 'id' => $symbol];
            $subs[] = ['sub' => "market.{$symbol}.kline.1min", 'id' => $symbol];
            $subs[] = ['sub' => "market.{$symbol}.depth.step0", 'id' => $symbol];
            $subs[] = ['sub' => "market.{$symbol}.trade.detail", 'id' => $symbol];
        }

        // 每批发5个，间隔0.5秒
        $batchSize = 5;
        $chunks = array_chunk($subs, $batchSize);
        foreach ($chunks as $i => $chunk) {
            \Workerman\Lib\Timer::add(0.5 * $i, function () use ($con, $chunk, $i) {
                foreach ($chunk as $sub) {
                    if ($con->getStatus() === \Workerman\Connection\TcpConnection::STATUS_ESTABLISHED) {
                        $con->send(json_encode($sub));
                    }
                }
            }, [], false); // false = 只执行一次
        }

        self::debugLog("Scheduled " . count($chunks) . " batches, total " . count($subs) . " subscriptions");
    }

    /**
     * 处理 HTX WebSocket 消息
     * HTX 发送 gzip 压缩的二进制数据
     */
    public static function onAsyncMessage($con, $message, $worker)
    {
        // HTX 消息是 gzip 压缩的二进制数据，需要解压
        $data = json_decode($message, true);
        if (!$data) {
            // 尝试 gzip 解压
            $unzipped = @gzdecode($message);
            if (!$unzipped) {
                $unzipped = @gzinflate($message);
            }
            if (!$unzipped) {
                // 尝试跳过2字节gzip头
                $unzipped = @gzinflate(substr($message, 2));
            }
            if ($unzipped) {
                $data = json_decode($unzipped, true);
            }
        }

        if (!$data) {
            static $failCount = 0;
            $failCount++;
            if ($failCount <= 5) {
                self::debugLog("Failed to decode message, len=" . strlen($message) . ", hex(first20)=" . bin2hex(substr($message, 0, 20)));
            }
            return;
        }

        // HTX 心跳处理：收到 ping 必须回 pong，否则服务器断开连接
        if (isset($data['ping'])) {
            $con->send(json_encode(['pong' => $data['ping']]));
            static $pingCount = 0;
            $pingCount++;
            if ($pingCount <= 3 || $pingCount % 100 === 0) {
                self::debugLog("PING/PONG #{$pingCount}");
            }
            return;
        }

        // 订阅确认
        if (isset($data['subbed'])) {
            self::debugLog("Subscribed: {$data['subbed']}, status={$data['status']}");
            return;
        }

        // 数据推送
        if (!isset($data['ch'])) {
            return;
        }

        $ch = $data['ch'];
        $tick = $data['tick'] ?? [];
        $chParts = explode('.', $ch);
        // ch 格式: market.btcusdt.ticker / market.btcusdt.kline.1min / market.btcusdt.depth.step0 / market.btcusdt.trade.detail
        $symbol = $chParts[1] ?? '';
        $dataType = $chParts[2] ?? '';

        $msg = [];
        $msgs = [];

        try {
            switch ($dataType) {
                case 'ticker':
                    $close = floatval($tick['close'] ?? 0);
                    $open  = floatval($tick['open'] ?? 0);
                    $change = ($open > 0) ? round(($close - $open) / $open * 100, 4) : 0;

                    static $tickerLogCount = 0;
                    $tickerLogCount++;
                    if ($tickerLogCount <= 10 || $tickerLogCount % 50 === 0) {
                        self::debugLog("TICKER #{$tickerLogCount} {$symbol}: close={$close}, open={$open}, change={$change}%");
                    }

                    $zero_table = 'market_' . $symbol . '_kline_1min';
                    KlineService::instance()->detectTable($zero_table);

                    $ladata = [
                        'open'        => $open,
                        'close'       => $close,
                        'high'        => floatval($tick['high'] ?? 0),
                        'low'         => floatval($tick['low'] ?? 0),
                        'change'      => $change,
                        'amount'      => floatval($tick['amount'] ?? 0),
                        'count'       => intval($tick['count'] ?? 0),
                        'volume'      => floatval($tick['vol'] ?? 0),
                        'last_price'  => $close,
                        'update_time' => time(),
                    ];

                    $affectedRows = Db::name('product_lists')->where([['code', '=', $symbol]])->update($ladata);
                    if ($tickerLogCount <= 10 || $tickerLogCount % 50 === 0) {
                        self::debugLog("DB update product_lists where code={$symbol}, affected={$affectedRows}");
                    }
                    break;

                case 'kline':
                    $msg['type']   = 'tradingvew';
                    $msg['ch']     = $ch;
                    $msg['symbol'] = $symbol;
                    $msg['period'] = '1min';
                    $msg['open']   = floatval($tick['open'] ?? 0);
                    $msg['close']  = floatval($tick['close'] ?? 0);
                    $msg['low']    = floatval($tick['low'] ?? 0);
                    $msg['high']   = floatval($tick['high'] ?? 0);
                    $msg['vol']    = floatval($tick['vol'] ?? 0);
                    $msg['count']  = intval($tick['count'] ?? 0);
                    $msg['amount'] = floatval($tick['amount'] ?? 0);
                    $msg['time']   = intval($tick['id'] ?? 0);
                    $msg['ranges'] = fox_time(intval($tick['id'] ?? 0));
                    $es_table = 'market_' . $symbol . '_kline_1min';
                    try {
                        KlineService::instance()->save($es_table, $msg);
                    } catch (\Throwable $e) {
                        self::debugLog("KlineService save error: " . $e->getMessage());
                    }
                    break;

                case 'depth':
                    $msg['type']   = 'depthlist';
                    $msg['market'] = $symbol;
                    $msg['bid']    = [];
                    $msg['ask']    = [];
                    $bids = $tick['bids'] ?? [];
                    $asks = $tick['asks'] ?? [];
                    for ($i = 0; $i < count($bids); $i++) {
                        $msg['bid'][$i]['id']       = $i;
                        $msg['bid'][$i]['price']    = floatval($bids[$i][0]);
                        $msg['bid'][$i]['quantity'] = floatval($bids[$i][1]);
                        $msg['bid'][$i]['total']    = ($i == 0) ? floatval($bids[$i][1]) : floatval($bids[$i][1]) + floatval($bids[$i - 1][1]);
                    }
                    for ($i = 0; $i < count($asks); $i++) {
                        $msg['ask'][$i]['id']       = $i;
                        $msg['ask'][$i]['price']    = floatval($asks[$i][0]);
                        $msg['ask'][$i]['quantity'] = floatval($asks[$i][1]);
                        $msg['ask'][$i]['total']    = ($i == 0) ? floatval($asks[$i][1]) : floatval($asks[$i][1]) + floatval($asks[$i - 1][1]);
                    }
                    $msgs['bid'] = json_encode($msg['bid']);
                    $msgs['ask'] = json_encode($msg['ask']);
                    $stable = 'depthlist_' . $symbol;
                    self::hbrds()->write($stable, $msgs);
                    break;

                case 'trade':
                    $tradeData = $tick['data'] ?? [];
                    if (!empty($tradeData)) {
                        $trade = $tradeData[0]; // 取第一条成交
                        $msgs['type'] = 'tradelog';
                        $msgs['data'] = json_encode([
                            'market'     => $symbol,
                            'id'         => $trade['ts'] ?? 0,
                            'price'      => floatval($trade['price'] ?? 0),
                            'num'        => floatval($trade['amount'] ?? 0),
                            'tradeId'    => $trade['tradeId'] ?? $trade['id'] ?? 0,
                            'trade_type' => (isset($trade['direction']) && $trade['direction'] == 'sell') ? 2 : 1,
                            'time'       => substr((string)($trade['ts'] ?? 0), 0, 10),
                        ]);
                        $stable = 'tradelogs_' . $symbol;
                        self::hbrds()->write($stable, $msgs);
                    }
                    break;
            }
        } catch (\Throwable $e) {
            self::debugLog("onAsyncMessage ERROR [{$dataType}][{$symbol}]: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
    }
    public static function saveLog($symbol, $msg)
    {
        $dir = __DIR__ . "/runtime/workerman/";
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        $today = date('Ymd');
        $file_path = $dir . "/kline-" . $symbol . "-" . $today . ".log";
        $handle = fopen($file_path, "a+");
        @fwrite($handle, date("H:i:s") . $msg . "\r\n");
        @fclose($handle);
    }
}