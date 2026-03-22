<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class NewsLists extends TimeModel
{

    protected $name = "news_lists";

    protected $deleteTime = "delete_time";

    
    public function newsCate()
    {
        return $this->belongsTo('\app\admin\model\NewsCate', 'cate_id', 'id');
    }

    
    public function getNewsCateList()
    {
        return \app\admin\model\NewsCate::column('title', 'id');
    }

}