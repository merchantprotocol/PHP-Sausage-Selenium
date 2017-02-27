<?php

namespace MP\Fixtures\App;

trait Service
{
    /**
     * Wait until element becomes hidden
     * 
     * @param $selector css selector
     * @param int $timeout in seconds
     */
    protected function waitForHidden($selector, $timeout = 2)
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

    /**
     * Wait until element becomes visible
     * 
     * @param $selector css selector
     * @param int $timeout in seconds
     */
    protected function waitForDisplayed($selector, $timeout = 2)
    {
        if (!is_string($selector)) {
            return;
        }

        $this->waitUntil(function() use($selector){
            try{
                $element = $this->byCssSelector($selector);

                if ($element->displayed()) {
                    return true;
                }

                return null;
            } catch (Exception $e) {
                return null;
            }

        }, $timeout * 1000);
    }
}