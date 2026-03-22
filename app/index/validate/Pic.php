<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-01-21 16:51:04
 * @LastEditTime: 2021-09-12 16:32:42
 * @Description: Forward, no stop
 */
declare (strict_types = 1);

namespace app\index\validate;

use think\Validate;

class Pic extends Validate
{

	protected $rule = [
        'file' => [
            // 限制文件大小(单位b)，这里限制为4M
            'fileSize' => 4 * 1024 * 1024,
            // 限制文件后缀，多个后缀以英文逗号分割
            'fileExt'  => 'jpg,jpeg,png,bmp,gif',
            'fileMime' => 'image/jpeg,image/png,image/gif',
        ]
    ];
    

    protected $message = [
        'file' => [
            // 限制文件大小(单位b)，这里限制为4M
            'fileSize' => 'finance.file_size',
            // 限制文件后缀，多个后缀以英文逗号分割
            'fileExt'  => 'finance.file_ext',
            'fileMime' => 'finance.file_mime',
        ]
    ];
}
