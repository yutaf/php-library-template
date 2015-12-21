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
    protected $css = array();
    protected $js = array();
    protected $document_root_basedir = 'htdocs';
    protected $template_basedir = 'templates';

    abstract protected function setGlobalVariables();
    abstract protected function setBlocks();

    /**
     * Init
     */
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

    protected function getDefaultTemplate()
    {
        return $this->getSamePathHtml();
    }

    /**
     * prepare
     *
     * @param $template
     * @throws \LogicException
     */
    protected function prepare($template)
    {
        if(! isset($template) || strlen($template) == 0) {
            $template = $this->getDefaultTemplate();
        }
        if(! is_file($template)) {
            throw new \LogicException('Indicated template does not exist. name: '.$template);
        }
        $result = $this->loadTemplatefile($template);
        if(! $result) {
            throw new \LogicException('Failed to load: '.$template);
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
    protected function getSamePathHtml()
    {
        $template_root = $this->getTemplateRoot();
        $script_filename = basename($_SERVER['SCRIPT_NAME']);
        $template_filename = basename($_SERVER['SCRIPT_NAME'], 'php').'html';
        $template_path = str_replace($script_filename, $template_filename, $_SERVER['SCRIPT_NAME']);
        return $template_root.$template_path;
    }

    /**
     * get template root directory
     *
     * @return mixed
     */
    protected function getTemplateRoot()
    {
        $root = str_replace($this->document_root_basedir, $this->template_basedir, $_SERVER['DOCUMENT_ROOT']);
        if(substr($root, -1, 1) === '/') {
            $root = substr_replace($root, '', -1);
        }
        return $root;
    }

    /**
     * 文字列をエスケープ
     *
     * @param $str
     * @param int $flags
     * @param string $encoding
     * @return string
     */
    public function escape($str, $flags=ENT_QUOTES, $encoding='UTF-8')
    {
        return htmlspecialchars($str, $flags, $encoding);
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
