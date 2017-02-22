<?php

namespace MP\Fixtures\App;

trait Service
{

    /**
     * @param $selector css selector
     * @param int $timeout in seconds
     */
    protected function waitUntilHide($selector, $timeout = 2)
    {
        if (!is_string($selector)) {
            return;
        }
        
        $this->waitUntil(function() use($selector){
            try{
                $element = $this->byCssSelector($selector);
                
                if ($element->displayed()) {
                    return null;
                }
                
                return true;
            } catch (Exception $e) {
                return null;
            }

        }, $timeout * 1000);
    }
}