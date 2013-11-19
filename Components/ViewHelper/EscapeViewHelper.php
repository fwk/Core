<?php
namespace Fwk\Core\Components\ViewHelper;

class EscapeViewHelper extends AbstractViewHelper implements ViewHelper
{
    protected $quoteStyle;
    protected $charset;
    
    public function __construct($quoteStyle = ENT_QUOTES, $charset = "utf-8")
    {
        $this->quoteStyle = $quoteStyle;
        $this->charset = $charset;
    }
    
    public function execute(array $arguments)
    {
        $str = (isset($arguments[0]) ? $arguments[0] : "");
        
        return htmlentities($str, $this->quoteStyle, $this->charset);
    }
}