<?php

/**
 * Class TemplateManager
 *
 * @author Yuta Fujishiro<yutafuji2008@gmail.com>
 */
namespace Yutaf;

class TemplateManager
{
    protected $tpl;
    protected $invoker;
    protected $variables;
    protected $bridges = array();
    protected $commonBridgePrefix;

    /**
     * コンストラクタ
     *
     * @param $tpl
     * @param array $variables
     */
    public function __construct(\HTML_Template_IT $tpl, $variables=array())
    {
        // デリミタの設定
        $tpl->openingDelimiter='{{';
        $tpl->closingDelimiter='}}';
        // インスタンス代入
        $this->tpl = $tpl;

        $this->invoker = $this->getInvoker();
        $this->variables = $variables;
        $this->commonBridgePrefix = $this->getCommonBridgePrefix();
    }

    /**
     * set variables
     *
     * @param $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * add variables
     *
     * @param $variables
     */
    public function addVariables($variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    /**
     * ブリッジ インスタンスを取得
     *
     * @param string $bridge_name
     * @return mixed
     */
    public function get($bridge_name='')
    {
        if (!isset($this->bridges[$bridge_name])) {
            $bridge_class = $bridge_name . 'Bridge';
            $bridge = new $bridge_class($this->tpl, $this->invoker, $this->variables);

            $this->bridges[$bridge_name] = $bridge;
        }

        return $this->bridges[$bridge_name];
    }

    /**
     * プログラムのパス構成と同名のブリッジを取得
     *
     * @return string
     */
    public function getOwnBridgeName()
    {
        $str = str_replace('.php', '', substr($_SERVER['SCRIPT_NAME'], 1));
        return $this->getBridgeStr($str);
    }

    /**
     * Base ブリッジを返す
     *
     * @return string
     */
    public function getBaseBridgeName()
    {
        return $this->commonBridgePrefix.'Base';
    }

    /**
     * Menu ブリッジを返す
     *
     * @return string
     */
    public function getMenuBridgeName()
    {
        return $this->commonBridgePrefix.'Menu';
    }

    /**
     * Validate ブリッジを返す
     *
     * @return string
     */
    public function getValidateBridgeName()
    {
        return $this->commonBridgePrefix.'Validate';
    }

    /**
     * Paging ブリッジを返す
     *
     * @return string
     */
    public function getPagingBridgeName()
    {
        return $this->commonBridgePrefix.'Paging';
    }

    /**
     * Error ブリッジを返す
     *
     * @return string
     */
    public function getErrorBridgeName()
    {
        return $this->commonBridgePrefix.'Error';
    }

    /**
     * 共通のブリッジ接頭句を返す
     *
     * @return string
     * @throws LogicException
     */
    protected function getCommonBridgePrefix()
    {
        $paths = array(
            '/admin/'
        );
        foreach($paths as $path) {
            if($this->hasStr($this->invoker, $path)) return $this->getBridgeStr($path);
        }
        return '';
    }

    /**
     * メソッドの呼び出し元ファイル名を取得
     *
     * @return mixed
     */
    protected function getInvoker()
    {
        $backtrace = debug_backtrace();
        $last = end($backtrace);
        return $last['file'];
    }

    /**
     * 指定文字列を持つか判定
     *
     * @param $haystack
     * @param $str
     * @return bool
     */
    protected function hasStr($haystack, $str)
    {
        if(strpos($haystack, $str) !== false) return true;
        return false;
    }

    /**
     * ブリッジ用の文字列を返す
     *
     * @param $path
     * @return string
     */
    protected function getBridgeStr($path)
    {
        if(substr($path, 0, 1) == '/') $path = substr($path, 1);
        if(substr($path, -1) == '/') $path = rtrim($path, '/');
        $pieces = explode('/', $path);
        $str = '';
        foreach($pieces as $piece) {
            $str .= ucfirst($piece);
        }
        
        return $str;
    }

    /**
     * 複数のテンプレート・ブリッジをデフォルトテンプレートでレンダリング
     *
     * @param array $bridges
     */
    public function renderBridges($bridges=array())
    {
        if(count($bridges) == 0) return;
        foreach($bridges as $bridge) {
            if(strlen($bridge) > 0) $this->get($bridge)->render();
        }
    }
}
