<?php
namespace mon\template;

/**
 * 基于Bootstrap的分页类
 *
 * @author Mon <985558837@qq.com>
 * @version v1.1 2017-07-21
 * @update 独立化css组件配置
 */
class Page
{
    /**
     * 分页URL根路径
     *
     * @var [type]
     */
    public $baseUrl;

    /**
     * 起始行数
     *
     * @var [type]
     */
    public $firstRow;

    /**
     * 列表每页显示行数
     *
     * @var [type]
     */
    public $listRows;

    /**
     * 分页跳转时要带的参数
     *
     * @var [type]
     */
    public $parameter;

    /**
     * 总行数
     *
     * @var [type]
     */
    public $totalRows;

    /**
     * 分页总页面数
     *
     * @var [type]
     */
    public $totalPages;

    /**
     * 分页栏每页显示的页数
     *
     * @var integer
     */
    public $rollPage = 6;

    /**
     * 最后一页是否显示总页数
     *
     * @var boolean
     */
    public $lastSuffix = true;

    /**
     * 分页参数名
     *
     * @var string
     */
    private $p = 'page';

    /**
     * 当前链接URL
     *
     * @var string
     */
    private $url = '';

    /**
     * 当前页
     *
     * @var integer
     */
    private $nowPage = 1;

    /**
     * 样式库
     *
     * @var string
     */
    private $css = "
        <style>
        .lmf_pagination {display: inline-block;padding-left: 0;margin: 20px 0;border-radius: 4px;}
        .lmf_pagination > li {display: inline;}
        .lmf_pagination > li > a, .lmf_pagination > li > span {position: relative;float: left;padding: 6px 12px;margin-left: -1px;line-height: 1.42857143;color: #337ab7;text-decoration: none;background-color: #fff;border: 1px solid #ddd;}
        .lmf_pagination > li:first-child > a, .lmf_pagination > li:first-child > span {margin-left: 0;border-top-left-radius: 4px;border-bottom-left-radius: 4px;}
        .lmf_pagination > li:last-child > a, .lmf_pagination > li:last-child > span {border-top-right-radius: 4px;border-bottom-right-radius: 4px;}
        .lmf_pagination > li > a:hover, .lmf_pagination > li > span:hover, .lmf_pagination > li > a:focus, .lmf_pagination > li > span:focus {z-index: 2;color: #23527c;background-color: #eee;border-color: #ddd;}
        .lmf_pagination > .active > a, .lmf_pagination > .active > span, .lmf_pagination > .active > a:hover, .lmf_pagination > .active > span:hover, .lmf_pagination > .active > a:focus, .lmf_pagination > .active > span:focus {z-index: 3;color: #fff;cursor: default;background-color: #337ab7;border-color: #337ab7;}
        .lmf_pagination > .disabled > span, .lmf_pagination > .disabled > span:hover, .lmf_pagination > .disabled > span:focus, .lmf_pagination > .disabled > a, .lmf_pagination > .disabled > a:hover, .lmf_pagination > .disabled > a:focus {color: #777;cursor: not-allowed;background-color: #fff; border-color: #ddd;}
        .lmf_pagination-sm > li > a, .lmf_pagination-sm > li > span {padding: 5px 10px;font-size: 12px;line-height: 1.5;}
        .lmf_pagination-sm > li:first-child > a, .lmf_pagination-sm > li:first-child > span {border-top-left-radius: 3px;border-bottom-left-radius: 3px;}
        .lmf_pagination-sm > li:last-child > a, .lmf_pagination-sm > li:last-child > span {border-top-right-radius: 3px;border-bottom-right-radius: 3px;}
        .lmf_pagination-lg > li > a, .lmf_pagination-lg > li > span {padding: 10px 16px;font-size: 18px;line-height: 1.3333333;}
        .lmf_pagination-lg > li:first-child > a, .lmf_pagination-lg > li:first-child > span {border-top-left-radius: 6px;border-bottom-left-radius: 6px;}
        .lmf_pagination-lg > li:last-child > a, .lmf_pagination-lg > li:last-child > span {border-top-right-radius: 6px;border-bottom-right-radius: 6px;}
        </style>
    ";

    /**
     * 分页显示定制
     *
     * @var array
     */
    private $config  = [
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        'prev'   => '&laquo;',
        'next'   => '&raquo;',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    ];

    /**
     * 当前选择样式大小
     *
     * @var [type]
     */
    private $size_type;

    // 样式大小
    private $size = [
        'def'   => '',
        'sm'    => 'lmf_pagination-sm',
        'lg'    => 'lmf_pagination-lg'
    ];

    /**
     * 注册分页配置
     *
     * @param string $url        分页URL根路径
     * @param array  $totalRows  总的记录数
     * @param array  $listRows   每页显示记录数
     * @param string $p          分页参数名
     * @param array  $parameter  分页跳转的参数
     * @return [type] [description]
     */
    public function register($baseUrl, $totalRows, $listRows = 20, $p = 'page', $sizeType = "sm", $parameter = [])
    {
        // 基础设置
        $this->baseUrl    = $baseUrl;
        $this->size_type  = in_array($sizeType, array('def', 'sm', 'lg')) ? $sizeType : 'def';
        $this->p          = $p;         // 分页参数名
        $this->totalRows  = $totalRows; // 设置总记录数
        $this->listRows   = $listRows;  // 设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage > 0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);

        return $this;
    }

    /**
     * 定制分页链接设置
     *
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }

        return $this;
    }

    /**
     * 组装分页链接
     *
     * @return string
     */
    public function show()
    {
        if (0 == $this->totalRows) return '';

        // 生成URL
        $this->parameter[$this->p] = '_PAGE_NUM_';
        $this->url = $this->baseUrl . '?' . http_build_query($this->parameter);

        // 计算分页信息
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        // 计算分页临时变量
        $now_cool_page      = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;

        // 上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<li><a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a></li>' : '';

        // 下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<li><a class="next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a></li>' : '';

        // 第一页
        $the_first = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
            $the_first = '<li><a class="first" href="' . $this->url(1) . '">' . $this->config['first'] . '</a></li>';
        }

        // 最后一页
        $the_end = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
            $the_end = '<li><a class="end" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a><li>';
        }

        // 数字连接
        $link_page = "";
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if ($page > 0 && $page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $link_page .= '<li><a class="num" href="' . $this->url($page) . '">' . $page . '</a><li>';
                } else {
                    break;
                }
            } else {
                if ($page > 0 && $this->totalPages != 1) {
                    $link_page .= '<li class="active"><span class="current">' . $page . '</span></li>';
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            ['%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'],
            [$this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages],
            $this->config['theme']
        );

        return $this->css . "<div><ul class=\"lmf_pagination {$this->size[$this->size_type]}\">{$page_str}<ul></div>";
    }

    /**
     * 生成链接URL
     *
     * @param  integer $page 页码
     * @return string
     */
    private function url($page)
    {
        return str_replace('_PAGE_NUM_', $page, $this->url);
    }
}
