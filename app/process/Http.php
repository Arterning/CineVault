<?php

namespace app\process;

use Webman\App;

class Http extends App
{
    /**
     * OnWorkerStart.
     * @param $worker
     * @return void
     */
    public function onWorkerStart($worker)
    {
        // 设置内存限制
        ini_set('memory_limit', '1512M');
        ini_set('upload_max_filesize', '1500M');
        ini_set('post_max_size', '1500M');
        ini_set('max_execution_time', '300');
        ini_set('max_input_time', '300');
        
        // 调用父类方法
        parent::onWorkerStart($worker);
    }
}