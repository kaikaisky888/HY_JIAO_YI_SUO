<?php

namespace app\admin\model;

use app\common\model\TimeModel;

class ProductLists extends TimeModel
{

    protected $name = "product_lists";

    protected $deleteTime = "delete_time";

    /**
     * logo访问器 - 自动补全路径前缀
     */
    public function getLogoAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        // 已经是完整URL(http/https)直接返回
        if (stripos($value, 'http') === 0) {
            return $value;
        }
        // 相对路径补上 / 前缀
        return '/' . ltrim($value, '/');
    }

    public function productCate()
    {
        return $this->belongsTo('\app\admin\model\ProductCate', 'cate_id', 'id');
    }

    

}