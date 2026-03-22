<?php

/*
 * @Author: Fox Blue
 * @Date: 2021-07-14 17:18:15
 * @LastEditTime: 2021-08-15 15:35:41
 * @Description: Forward, no stop
 */
namespace app\common\service;

use Elasticsearch\ClientBuilder;
class ElasticService
{
    public $client = NULL;
    public function __construct()
    {
        $es_hosts = \think\facade\Config::get('cache.elasticsearch');
        //连接ip 端口
        $this->client = ClientBuilder::create()->setHosts([$es_hosts['host'] . ':' . $es_hosts['port']])->build();
    }
    public function __destruct()
    {
        $this->client = NULL;
    }
    public function create_index($index)
    {
        $type = ["type" => ['type' => 'keyword'], "ch" => ['type' => 'keyword'], "symbol" => ['type' => 'keyword'], "period" => ['type' => 'keyword'], "open" => ['type' => 'double'], "close" => ['type' => 'double'], "low" => ['type' => 'double'], "vol" => ['type' => 'double'], "high" => ['type' => 'double'], "count" => ['type' => 'integer'], "amount" => ['type' => 'double'], "time" => ['type' => 'long'], "ranges" => ['type' => 'keyword']];
        $params = ['index' => $index, 'body' => ['mappings' => ['_source' => ['enabled' => 'true'], 'properties' => $type]]];
        return $this->client->indices()->create($params);
    }
    public function exist_index($index)
    {
        $params = ['index' => $index];
        return $this->client->indices()->exists($params);
    }
    /**获取索引结构
     * @return array
     */
    public function get_index($index)
    {
        $params = ['index' => $index];
        return $this->client->indices()->get($params);
    }
    public function delete_index($index)
    {
        $params = array();
        $params['index'] = $index;
        return $this->client->indices()->delete($params);
    }
    public function index_doc($index, $doc, $id)
    {
        $params = array();
        $params['index'] = $index;
        $params['id'] = $id;
        $params['body'] = $doc;
        return $this->client->index($params);
    }
    public function update_doc($index, $doc, $id = NULL)
    {
        $params = array();
        $params['index'] = $index;
        $params['body'] = $doc;
        if ($id != NULL) {
            $params['id'] = $id;
        }
        return json_encode($this->client->index($params));
    }
    public function get_doc($index, $id)
    {
        $params = array();
        $params['index'] = $index;
        $params['id'] = $id;
        $this->client->get($params);
    }
    public function exist_doc($index, $id)
    {
        $params = array();
        $params['index'] = $index;
        $params['id'] = $id;
        return $this->client->indices()->exists($params);
    }
    public function delete_doc($index, $id)
    {
        $params = array();
        $params['index'] = $index;
        $params['id'] = $id;
        return $this->client->delete($params);
    }
    public function search_one($index, $time)
    {
        $params = ['index' => $index, 'body' => ['size' => 1, 'sort' => ['time' => ['order' => 'desc']], 'query' => ['range' => ['time' => ['lte' => $time]]]]];
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['hits']['total']['value']) && !isset($response['aggregations'])) {
            if ($response['hits']['total']['value'] > 0) {
                //循环数据
                foreach ($response['hits']['hits'] as $value) {
                    $data[] = $value['_source'];
                }
            }
        }
        return $data;
    }
    public function search_one_day($index, $time)
    {
        $params = ['index' => $index, 'body' => ['size' => 1, 'sort' => ['time' => ['order' => 'asc']], 'query' => ['range' => ['time' => ['gte' => $time]]]]];
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['hits']['total']['value']) && !isset($response['aggregations'])) {
            if ($response['hits']['total']['value'] > 0) {
                //循环数据
                foreach ($response['hits']['hits'] as $value) {
                    $data[] = $value['_source'];
                }
            }
        }
        return $data;
    }
    public function search_two($index)
    {
        $params = ['index' => $index, 'body' => ['size' => 2, 'sort' => ['time' => ['order' => 'desc']]]];
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['hits']['total']['value']) && !isset($response['aggregations'])) {
            if ($response['hits']['total']['value'] > 0) {
                //循环数据
                foreach ($response['hits']['hits'] as $value) {
                    $data[] = $value['_source'];
                }
            }
        }
        return $data;
    }
    public function search_svg($index, $type = '1min', $size = 20)
    {
        $types = '*,' . $type . '*';
        $params = ['index' => $index, 'body' => ['size' => $size, 'sort' => ['time' => ['order' => 'desc']], 'query' => ["wildcard" => ["ranges" => $types]]]];
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['hits']['total']['value']) && !isset($response['aggregations'])) {
            if ($response['hits']['total']['value'] > 0) {
                //循环数据
                foreach ($response['hits']['hits'] as $value) {
                    $data[] = $value['_source'];
                }
            }
        }
        $datas = [];
        if (isset($data)) {
            foreach ($data as $k => $v) {
                $datas[$k] = (double) $v['close'];
            }
        }
        $datas = array_reverse($datas);
        return $datas;
    }
    public function search($index, $from = null, $to = null, $type = '1min', $size = 200)
    {
        $types = '*,' . $type . '*';
        if ($from && $to) {
            $params = ['index' => $index, 'body' => ['size' => $size, 'sort' => ['time' => ['order' => 'desc']], 'query' => ['bool' => ['must' => ['range' => ['time' => ['gte' => $from, 'lte' => $to]]], 'filter' => ["wildcard" => ["ranges" => $types]]]]]];
        } else {
            $params = ['index' => $index, 'body' => ['size' => $size, 'sort' => ['time' => ['order' => 'desc']], 'query' => ["wildcard" => ["ranges" => $types]]]];
        }
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['hits']['total']['value']) && !isset($response['aggregations'])) {
            if ($response['hits']['total']['value'] > 0) {
                //循环数据
                foreach ($response['hits']['hits'] as $value) {
                    $data[] = $value['_source'];
                }
            }
        }
        $datas = [];
        $num = count($data);
        if ($data && $num > 1) {
            foreach ($data as $k => $v) {
                $datas[$k]['id'] = (int) $v['time'];
                $datas[$k]['amount'] = (double) $v['amount'];
                if ($k == 0) {
                    $datas[$k]['open'] = (double) $data[$k]['open'];
                    $datas[$k]['close'] = (double) $data[$k]['close'];
                } else {
                    $datas[$k]['open'] = (double) $data[$k - 1]['close'];
                    $datas[$k]['close'] = (double) $data[$k]['close'];
                }
                $datas[$k]['high'] = (double) $v['high'];
                $datas[$k]['low'] = (double) $v['low'];
                $datas[$k]['vol'] = (double) $v['vol'];
                $datas[$k]['volume'] = (double) $v['vol'];
                $datas[$k]['count'] = (int) $v['count'];
                $datas[$k]['time'] = (int) $v['time'];
            }
        } else {
            foreach ($data as $k => $v) {
                $datas[$k]['id'] = (int) $v['time'];
                $datas[$k]['amount'] = (double) $v['amount'];
                $datas[$k]['open'] = (double) $v['close'];
                $datas[$k]['close'] = (double) $v['open'];
                $datas[$k]['high'] = (double) $v['high'];
                $datas[$k]['low'] = (double) $v['low'];
                $datas[$k]['vol'] = (double) $v['vol'];
                $datas[$k]['volume'] = (double) $v['vol'];
                $datas[$k]['count'] = (int) $v['count'];
                $datas[$k]['time'] = (int) $v['time'];
            }
        }
        $datas = array_reverse($datas);
        return $datas;
    }
    public function search_day($index, $size = 0)
    {
        $types = '*,1min*';
        $from = strtotime(date('Y-m-d'));
        $to = time();
        $params = ['index' => $index, 'body' => ['size' => $size, 'query' => ['bool' => ['must' => ['range' => ['time' => ['gte' => $from, 'lte' => $to]]], 'filter' => ["wildcard" => ["ranges" => $types]]]], 'aggs' => ['high' => ['max' => ["field" => "close"]], 'low' => ['min' => ["field" => "close"]], 'volume' => ['sum' => ["field" => "vol"]], 'amount' => ['sum' => ["field" => "amount"]], 'count' => ['sum' => ["field" => "count"]]]]];
        $response = $this->client->search($params);
        $data = [];
        if (isset($response['aggregations'])) {
            $data['high'] = $response['aggregations']['high']['value'];
            $data['low'] = $response['aggregations']['low']['value'];
            $data['volume'] = $response['aggregations']['volume']['value'];
            $data['amount'] = $response['aggregations']['amount']['value'];
            $data['count'] = $response['aggregations']['count']['value'];
        }
        return $data;
    }
    public function get_map($index)
    {
        $params = ['index' => $index];
        return $this->client->indices()->getMapping($params);
    }
    public function get_search($index, $type = 1, $from = null, $to = null)
    {
        $es_hosts = \think\facade\Config::get('cache.elasticsearch');
        //连接ip 端口
        if ($type == 1) {
            $query = [];
        }
        $url = $es_hosts['host'] . ':' . $es_hosts['port'] . '/' . $index . '/_search';
        // p($url);
        return $this->curl_post($url, '{"query":{ "match": { "symbol": "btcusdt" } }}');
    }
    public function curl_post($url, $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_URL => $url, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => $data, CURLOPT_RETURNTRANSFER => true]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}