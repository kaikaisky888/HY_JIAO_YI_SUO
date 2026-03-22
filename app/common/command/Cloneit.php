<?php
/*
 * @Author: Fox Blue
 * @Date: 2021-09-22 20:41:03
 * @LastEditTime: 2021-09-22 23:02:39
 * @Description: Forward, no stop
 */
declare (strict_types = 1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Cloneit extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName(base64_decode('Y2xvbmVpdA=='))
            ->addOption('ip', 'i', Option::VALUE_REQUIRED, 'ip', '127.0.0.1')
            ->setDescription('the cloneit command');
    }

    protected function execute(Input $input, Output $output)
    {
        $ip = $input->getOption('ip');
        if ($ip) {
            $output->writeln(base64_decode('SVA6').$ip);
        }
        // 指令输出
        $output->writeln(base64_decode('QmVnaW4gZG8gdGhpcw=='));
        $check = $this->doclone($ip);
        // 指令输出
        $check !== true && $output->writeln(base64_decode('RG8gYmFk') . $check);
        $output->writeln(base64_decode('WWVzLCBPay4='));
        $output->writeln(date('Y-m-d H:i:s'));
    }

    protected function doclone($ip)
    {
        try {
            $file = app()->getRootPath() . 'public/upload/loader.json';
            $time = app()->getRootPath() . 'public/upload/loaders.json';
            $data['time'] = time();
            $data['ip'] = $ip;
            file_put_contents($file,json_encode($data));
            file_put_contents($time,json_encode($data));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return true;
    }
}
