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

    /**
     * Scroll container to item
     * 
     * @param string $container element id
     * @param string $item element id
     * @return bool
     */
    protected function scrollTo($container, $item)
    {
        if (!is_string($container) || !is_string($item)) {
            return false;
        }

        $script = "return function() {
	        var scrollContainer = document.getElementById('{$container}');
            var item = document.getElementById('{$item}');
        
            if (!scrollContainer || !item) {
                return false;
            }
        
            scrollContainer.scrollTop = item.offsetTop;
        
            return true;
        }();";

        $result = $this->execute(
            array(
                'script' => $script,
                'args' => array()
            )
        );
        
        return $result ? true : false;
    }
}