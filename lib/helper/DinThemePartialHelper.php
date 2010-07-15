<?php

/**
 * This file is part of the dinThemePlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * Base theme partial helper
 * 
 * @package     dinThemePlugin.lib.helper
 * @subpackage  lib.helper
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class DinThemePartialHelper
{

    /**
     * Get partial
     * 
     * @param   string  $template   Template name or moduleName/templateName
     * @param   array   $vars       Template vars [optional]
     * @return  string  Rendered partial
     */
    static public function get( $template, $vars = array() )
    {

        if ( false !== $separator = strpos( $template, '/' ) )
        {
            $module   = substr( $template, 0, $separator );
            $action = '_' . substr( $template, $separator + 1 );
        }
        else
        {
            $module = sfContext::getInstance()->getActionStack()->getLastEntry()->getModuleName();
            $action = '_' . $template;
        }

        $view = new dinThemePartialView( sfContext::getInstance(), $module, $action, '' );
        $view->setPartialVars( $vars );
        return $view->render();

    } // DinThemePartialHelper::get()


    /**
     * Get layout slot
     * 
     * @param   string  $layout     Layout name
     * @param   string  $name       Slot name
     * @return  string  Rendered slot content
     */
    static public function slot( $layout, $name )
    {

        return sfContext::getInstance()->get( 'view_instance' )->getLayoutSlot( $layout, $name );

    } // DinThemePartialHelper::slot()


    /**
     * Get component
     * 
     * @param   string  $module     Module name
     * @param   string  $component  Component name
     * @param   array   $vars       Variables to be made accessible to the component
     * @return  string  Rendered component
     */
    static public function component( $module, $component, $vars = array() )
    {

        $view = new dinThemePartialView( sfContext::getInstance(), $module, '_' . $component, '' );
        return $view->renderComponent( $module, $component, $vars );

    } // DinThemePartialHelper::component()

} // DinThemePartialHelper

//EOF