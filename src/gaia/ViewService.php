<?php

declare(strict_types=1);

namespace app\service;

use mon\util\Tool;
use mon\util\Instance;
use mon\template\View;

/**
 * 视图服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ViewService extends View
{
    use Instance;

    /**
     * 生成URL
     *
     * @param string $url   url
     * @param array $params 附加参数
     * @return string
     */
    public function url(string $url, array $params = []): string
    {
        return Tool::instance()->buildURL($url, $params);
    }
}
