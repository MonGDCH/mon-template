<?php

namespace mon\template;

use Exception;
use ArrayAccess;
use mon\template\exception\ViewException;

/**
 * 视图引擎
 *
 * @author Mon  985558837@qq.com
 * @version v1.0
 *
 * @method fetch 		返回视图内容
 * @method display 		返回视图内容(不支持路径补全)
 * @method assign		设置视图数据
 * @method load 	    包含视图文件(include)
 * @method extend 		视图继承
 * @method block 		视图片段
 * @method blockEnd		视图片段结束
 * @method putBlock 	视图片段输出
 * @method content 		子视图内容输出
 * @method has 			是否存在某个视图数据
 * @method get 			获取视图数据
 */
class View implements ArrayAccess
{
    /**
     * 视图数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 视图目录路径
     *
     * @var [type]
     */
    protected $path;

    /**
     * 视图文件后缀
     *
     * @var string
     */
    protected $ext = '';

    /**
     * 视图嵌套级别
     *
     * @var integer
     */
    protected $offset = 0;

    /**
     * 继承的父视图
     *
     * @var array
     */
    protected $extends = [];

    /**
     * 视图的片段
     *
     * @var array
     */
    protected $sections = [];

    /**
     * 视图片段名
     *
     * @var array
     */
    protected $sectionStacks = [];

    /**
     * 未发现的视图片段
     *
     * @var array
     */
    protected $sectionsNotFound = [];

    /**
     * 构造方法
     *
     * @param string $path 视图目录路径
     * @param string $ext  视图文件后缀
     */
    function __construct($path = "", $ext = '')
    {
        $this->path = $path;
        $this->ext = $ext;
    }

    /**
     * 模版赋值
     *
     * @param  string|array $key   string: 模版变量名;array: 批量赋值
     * @param  mixed        $value 模版变量值
     * @return [type]        [description]
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, (array) $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 
     *
     * @param  [type] $value [description]
     * @param  array  $data  [description]
     * @return [type]        [description]
     */
    public function fetch($view, $data = [])
    {
        return $this->render($this->getViewPath($view), $data);
    }

    /**
     * 返回视图内容(不补全视图路径)
     *
     * @param  [type] $view [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function display($view, $data = [])
    {
        return $this->render($view  . '.' . $this->ext, $data);
    }

    /**
     * 包含视图文件
     * @param  [type] $view [description]
     * @return [type]       [description]
     */
    public function load($view, $data = [], $echo = true)
    {
        $view = $this->getContent($this->getViewPath($view), $data);

        if ($echo) {
            echo $view;
        } else {
            return $view;
        }
    }

    /**
     * 视图继承
     *
     * @return [type] [description]
     */
    public function extend($view)
    {
        $this->extends[$this->offset] = $this->getViewPath($view);
    }

    /**
     * 开始定义一个视图片段, 用户继承时的父级视图$this->with()输出
     * 一般 block() 与 blockEnd() 成对出现, 但传递第二个参数，则不需要 sectionEnd()
     *
     * @param string $name
     * @param string $content
     */
    public function block($name, $content = '')
    {
        ob_start();
        $this->sectionStacks[$this->offset][] = $name;

        if ($content) {
            $lastname = array_pop($this->sectionStacks[$this->offset]);
            $this->setSections($lastname, $content);
            ob_end_clean();
        }
    }

    /**
     * 结束定义一个视图片段
     *
     * @return string 视图片段标识符
     */
    public function blockEnd()
    {
        $lastname = array_pop($this->sectionStacks[$this->offset]);
        $this->setSections($lastname, ob_get_clean());
        return $lastname;
    }

    /**
     * 定义视图片段输出位置, 用于继承是父级视图输出子视图上报的视图片段
     *
     * @param string $name
     * @param string $content
     */
    public function putBlock($name, $content = '')
    {
        if (isset($this->sections[$this->offset][$name])) {
            echo $this->sections[$this->offset][$name];
        } else {
            $this->sectionsNotFound[$this->offset][] = $name;
            echo '<!--@section_' . $name . '-->';
        }

        if ($content) {
            $this->setSections($name, $content);
        }
    }

    /**
     * 输出视图数据 - content
     * 用于继承时，父级视图$this->content()输出使用
     *
     * @param  string  $node 获取内容的节点
     * @param  boolean $echo 是否直接输出
     * @return string        如果不是直接输出则返回内容
     */
    public function content($node = 'content', $echo = true)
    {
        $content = $this->getSections($node);

        if ($echo) {
            echo $content;
        } else {
            return $content;
        }
    }

    /**
     * 设置视图目录路径
     *
     * @param [type] $path 视图目录根路径
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 获取视图目录路径
     *
     * @return [type] 获取视图目录根路径
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 设置视图文件后缀
     *
     * @param [type] $ext
     * @return void
     */
    public function setExt($ext)
    {
        $this->ext = $ext;
        return $this;
    }

    /**
     * 获取视图文件后缀
     *
     * @return void
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * 视图数据是否存在
     *
     * @param  [type]  $key 视图变量名称
     * @return boolean      [description]
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 获取视图数据(支持'.'获取多级数据)
     *
     * @param  [type] $key     变量名，支持.分割数组
     * @param  [type] $default 默认值
     * @return [type]          [description]
     */
    public function get($key, $default = null)
    {
        $name = explode(".", $key);

        $result = $this->data;
        for ($i = 0, $len = count($name); $i < $len; $i++) {
            // 不存在配置节点，返回默认值
            if (!isset($result[$name[$i]])) {
                $result = $default;
                break;
            }
            $result = $result[$name[$i]];
        }

        return $result;
    }

    /**
     * 设置视图数据
     *
     * @param [type] $key   变量名
     * @param [type] $value 变量值
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, (array) $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 删除视图数据
     *
     * @param  [type] $key 变量名
     * @return [type]      [description]
     */
    public function del($key)
    {
        unset($this->data[$key]);
    }

    /**
     * 清空视图数据
     *
     * @return [type] [description]
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * 核心方法，渲染视图
     *
     * @param  string $view 视图路径
     * @param  array  $data 视图数据
     * @return [type]       [description]
     */
    protected function render($view, $data = [])
    {
        // 设置当前视图数据
        $this->set($data);
        // 提升视图嵌套级别
        $this->increase();
        // 获取视图内容
        $content = trim($this->getContent($view));

        // 判断当前视图级别是否存在继承, 存在继承，处理继承
        if (isset($this->extends[$this->offset])) {
            // 记录视图内容，到视图片段中
            $this->setSections('content', $content);
            // 获取父级视图
            $parent = $this->extends[$this->offset];
            $content = trim($this->getContent($parent));
        }

        // 判断是否存在未输出的变量
        if (isset($this->sectionsNotFound[$this->offset])) {
            // 对模板中未输出的变量进行替换
            foreach ($this->sectionsNotFound[$this->offset] as $name) {
                $content = str_replace('<!--@section_' . $name . '-->', $this->getSections($name), $content);
            }
        }

        // 重置视图片段
        $this->flush();
        // 降低嵌套级别
        $this->decrement();

        return $content;
    }

    /**
     * 添加视图片段
     *
     * @param  string $name     视图片段名称
     * @param  string $content  视图片段内容
     */
    protected function setSections($name, $content)
    {
        $this->sections[$this->offset][$name] = $content;
    }

    /**
     * 获取视图片段
     *
     * @param  string $name     视图片段名称
     * @return string
     */
    protected function getSections($name)
    {
        if (isset($this->sections[$this->offset][$name])) {
            return $this->sections[$this->offset][$name];
        } else {
            return '';
        }
    }

    /**
     * 处理获取视图内容
     *
     * @param  [type] $view 视图路径
     * @return [type]       [description]
     */
    protected function getContent($view, $data = [])
    {
        if (file_exists($view)) {
            // 开启缓存，利用缓存获取视图内容
            ob_start();
            // $data = array_merge($this->data, (array) $data);
            $data = !empty($data) ?: $this->data;
            // 数组变量分割
            extract($data);

            // 校验是否出现异常，出现异常清空缓存，防止污染程序
            try {
                include $view;
            } catch (Exception $e) {
                // 获取缓存，清空缓存
                ob_get_clean();
                throw $e;
            }
            // 返回视图内容，并清空缓存
            return ob_get_clean();
        } else {
            throw new ViewException("Cannot find the requested view: " . $view);
        }
    }

    /**
     * 获取视图路径
     *
     * @param  [type] $view 视图名称
     * @return [type]       [description]
     */
    protected function getViewPath($view)
    {
        if ($this->path) {
            $view = $this->path . ltrim($view, DIRECTORY_SEPARATOR);
        }

        return $view . '.' . $this->ext;
    }

    /**
     * 提升嵌套级别
     *
     * @return [type] [description]
     */
    protected function increase()
    {
        $this->offset++;
    }

    /**
     * 降低嵌套级别
     *
     * @return [type] [description]
     */
    protected function decrement()
    {
        $this->offset--;
    }

    /**
     * 重置视图片段
     *
     * @return void
     */
    protected function flush()
    {
        unset($this->sections[$this->offset],
        $this->sectionStacks[$this->offset],
        $this->sectionsNotFound[$this->offset]);
    }

    // +----------------------------------------------------------------------------
    // | 接口方法定义
    // +----------------------------------------------------------------------------

    /**
     * 接口方法，视图数据是否存在
     *
     * @param  [type]  $key 变量名称
     * @return boolean      [description]
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * 接口方法，获取视图数据，不存在则返回null
     *
     * @param  [type] $key     变量名称
     * @return [type]          [description]
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 接口方法，设置视图数据
     *
     * @param [type] $key   变量名称
     * @param [type] $value 默认值
     */
    public function offsetSet($key, $value = null)
    {
        return $this->set($key, $value);
    }

    /**
     * 接口方法，删除视图数据
     *
     * @param  [type] $key 变量名称
     * @return [type]      [description]
     */
    public function offsetUnset($key)
    {
        $this->del($key);
    }

    /**
     * 获取视图数据
     *
     * @param  string $key  变量名
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 添加视图数据
     *
     * @param string $key   变量名
     * @param mixed $value  变量值
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 视图数据是否存在
     *
     * @param  string  $key 变量名
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * 删除视图数据
     *
     * @param string $key 变量名
     */
    public function __unset($key)
    {
        $this->del($key);
    }
}
