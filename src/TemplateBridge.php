<?php

/**
 * Class TemplateBridge
 *
 * @author Yuta Fujishiro<yutafuji2008@gmail.com>
 */
namespace Yutaf;

abstract class TemplateBridge
{
    protected $tpl;
    protected $invoker;
    protected $variables;
    protected $base_dir;
    protected $css = array();
    protected $js = array();

    abstract protected function getDefaultTemplate();
    abstract protected function setGlobalVariables();
    abstract protected function setBlocks();
    protected function init()
    {
        // You can override in inherited class
    }
    
    /**
     * コンストラクタ
     *
     * @param $tpl
     * @param $invoker
     * @param array $variables
     */
    public function __construct(\HTML_Template_IT $tpl, $invoker, $variables=array())
    {
        $this->tpl = $tpl;
        $this->invoker = $invoker;
        $this->variables = $variables;
        $this->base_dir = $this->getBaseDir();
    }

    /**
     * テンプレート ベース ディレクトリを取得
     *
     * @return string
     */
    protected function getBaseDir()
    {
        switch(true) {
            //TODO 定義していく
            //case (strpos($this->invoker, '/admin/') !== false):
            //    $base_dir = TEMPLATE_DIR.'admin/';
            //    break;
            default:
                $script_dirname = dirname($_SERVER['SCRIPT_NAME']);
                $dir_name_following_root_path = ($script_dirname == '/')? '' : substr($script_dirname, 1).'/';

                $base_dir = TEMPLATE_DIR.$dir_name_following_root_path;
        }
        return ($this->isSpDir())? $base_dir.'sp/' : $base_dir;
    }

    /**
     * render
     *
     * @param null $template
     */
    public function render($template=null)
    {
        $this->prepare($template);
        $this->show();
    }

    /**
     * テンプレートhtmlを文字列で取得
     *
     * @param null $template
     * @return mixed
     */
    public function store($template=null)
    {
        $this->prepare($template);
        return $this->get();
    }

    /**
     * prepare
     *
     * @param $template
     * @throws LogicException
     */
    protected function prepare($template)
    {
        if(strlen($template) == 0) {
            $template = $this->getDefaultTemplate();
        }
        if(! is_file($template)) {
            throw new LogicException('Indicated template does not exist. name: '.$template);
        }
        $result = $this->loadTemplatefile($template);
        if(! $result) {
            throw new LogicException('Failed to load: '.$template);
        }
        $this->init();
        if(count($this->css) > 0) {
            $this->parseCss();
        }
        if(count($this->js) > 0) {
            $this->parseJs();
        }
        $this->setBlocks();
        $this->setGlobalBlock();
    }

    /**
     * set global block
     */
    protected function setGlobalBlock()
    {
        $this->setCurrentBlock();
        $this->setGlobalVariables();
        $this->parseCurrentBlock();
    }

    /**
     * css をパース
     */
    protected function parseCss()
    {
        if(count($this->css) < 1) return;
        foreach($this->css as $css) {
            $this->setCurrentBlock('css');
            $this->setVariable('css', $css);
            $this->parseCurrentBlock();
        }
    }

    /**
     * js をパース
     */
    protected function parseJs()
    {
        if(count($this->js) < 1) return;
        foreach($this->js as $js) {
            $this->setCurrentBlock('js');
            $this->setVariable('js', $js);
            $this->parseCurrentBlock();
        }
    }

    /**
     * cssファイルのlinkタグを追加する
     *
     * @param array $css
     */
    public function addCss($css=array())
    {
        if(! is_array($css)) $css = array($css);
        $this->css = array_merge($this->css, $css);
    }

    /**
     * jsを追加する
     *
     * @param array $js
     */
    public function addJs($js=array())
    {
        if(! is_array($js)) $js = array($js);
        $this->js = array_merge($this->js, $js);
    }
    
    /**
     * 呼び出し元のプログラムと同じパス構成を持つテンプレートを取得
     *
     * @return string
     */
    protected function getOwnTemplate()
    {
        // sp
        if($this->isSpDir()){
            return $this->getOwnTemplateSp();
        }
        // pc
        return TEMPLATE_DIR.substr(str_replace('.php', '.html', $_SERVER['SCRIPT_NAME']), 1);
    }

    /**
     * if you are in /sp directory
     *
     * @return bool
     */
    protected function isSpDir()
    {
        if(strpos($this->invoker, BASEDIR.'htdocs/sp/') === false) return false;
        return true;
    }

    /**
     * 呼び出し元のプログラムと同じパス構成を持つテンプレートを取得(スマホ用)
     *
     * @return string
     */
    private function getOwnTemplateSp()
    {
        $file = substr(str_replace('.php', '.html', $_SERVER['SCRIPT_NAME']), 1);

        $pieces = explode('/', $file);
        $last_key = end(array_keys($pieces));
        $template_dir_sp = 'sp';
        array_splice($pieces, $last_key, 0, $template_dir_sp);

        $file = implode('/', $pieces);
        return DIR_TEMPLATE.$file;
    }
    
    /**
     * 文字列をエスケープ
     *
     * @param $str
     * @return string
     * @throws LogicException
     */
    public function escape($str)
    {
        if(! defined('ENCODING_HTML_ESCAPE')) {
            throw new LogicException('ERROR: ENCODING_HTML_ESCAPE is not defined.');
        }
        return htmlspecialchars($str, ENT_QUOTES, ENCODING_HTML_ESCAPE);
    }

    /**
     * HTML_Template_IT::loadTemplatefile のラッパーメソッド
     *
     * @param $filename
     * @param bool $removeUnknownVariables
     * @param bool $removeEmptyBlocks
     * @return mixed
     */
    protected function loadTemplatefile($filename, $removeUnknownVariables=true, $removeEmptyBlocks=true)
    {
        return $this->tpl->loadTemplatefile($filename, $removeUnknownVariables, $removeEmptyBlocks);
    }

    /**
     * HTML_Template_IT::parse のラッパーメソッド
     *
     * @param $block
     */
    protected function parse($block)
    {
        $this->tpl->parse($block);
    }

    /**
     * HTML_Template_IT::parseCurrentBlock のラッパーメソッド
     */
    protected function parseCurrentBlock()
    {
        $this->tpl->parseCurrentBlock();
    }

    /**
     * HTML_Template_IT::setCurrentBlock のラッパーメソッド
     *
     * @param string $block
     */
    protected function setCurrentBlock($block = "__global__")
    {
        $this->tpl->setCurrentBlock($block);
    }

    /**
     * HTML_Template_IT::setVariable のラッパーメソッド
     *
     * @param $placeholder
     * @param $variable
     */
    protected function setVariable($placeholder, $variable)
    {
        $this->tpl->setVariable($placeholder, $variable);
    }

    /**
     * HTML_Template_IT::show のラッパーメソッド
     */
    protected function show()
    {
        $this->tpl->show();
    }

    /**
     * HTML_Template_IT::touchBlock のラッパーメソッド
     *
     * @param $block
     */
    protected function touchBlock($block='__global__')
    {
        $this->tpl->touchBlock($block);
    }

    /**
     * HTML_Template_IT::get() のラッパーメソッド
     *
     * @param string $block
     * @return string
     */
    protected function get($block = "__global__")
    {
        return $this->tpl->get($block);
    }
}
