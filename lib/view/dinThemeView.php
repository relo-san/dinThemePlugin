<?php

/**
 * This file is part of the dinThemePlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * A view that accept themes system
 * 
 * @package     dinThemePlugin
 * @subpackage  lib.view
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinThemeView extends dinHamlView
{

    /**
     * @var array   Layout slots holder
     */
    protected $slotHolder = null;


    /**
     * @var object  Template variables holder
     */
    protected $varHolder = null;


    /**
     * @var string  Theme name
     */
    protected $theme = null;


    /**
     * @var string  Theme path's
     */
    protected
        $themePath = null,
        $layoutPath = null,
        $modulePath = null;


    /**
     * Configures template
     * 
     * @return  void
     */
    public function configure()
    {

        $this->configureHaml();

        // store our current view
        $this->context->set( 'view_instance', $this );

        // require our configuration
        require( $this->context->getConfigCache()->checkConfig(
            'modules/' . $this->moduleName . '/config/view.yml'
        ) );
        require( $this->context->getConfigCache()->checkConfig(
            'modules/' . $this->moduleName . '/config/layout.yml'
        ) );

        $this->varHolder = new sfParameterHolder();
        $this->slotHolder = array();

        // set theme configuration
        $this->theme = $theme = sfConfig::get( 'app_view_theme', 'default' );
        $app_dir = sfConfig::get( 'sf_app_dir' );
        $this->themePath = sfConfig::get( 'theme_' . $theme . '_path', '/themes/' . $theme );
        $this->layoutPath = $app_dir . $this->themePath
                           . sfConfig::get( 'theme_' . $theme . '_layout_path', '/layouts' ) . '/';
        $this->modulePath = $app_dir . $this->themePath
                           . sfConfig::get( 'theme_' . $theme . '_module_path', '/modules') . '/'
                           . $this->moduleName;

        // set template directory
        if ( !$this->directory )
        {
            $this->setDirectory( $this->modulePath );
            $this->setDecoratorDirectory( $this->layoutPath );
        }

    } // dinThemeView::configure()


    /**
     * Build output
     * 
     * @param   string  $content    Action content
     * @return  string  Output string
     */
    protected function decorate( $content )
    {

        if ( sfConfig::get( 'sf_logging_enabled' ) )
        {
            $this->dispatcher->notify( new sfEvent(
                $this, 'application.log',
                array( sprintf( 'Build layout for "%s/%s"', $this->moduleName, $this->actionName ) )
            ) );
        }

        // add rendered action to slot
        $action = sfConfig::get( 'theme_' . $this->theme . '_action', array() );
        $action['order'] = isset( $action['order'] ) ? $action['order'] : 1;
        $this->setLayoutSlot( $action['parent'], $action['slot'], $content, $action['order'] );

        // build layout
        return $this->buildLayout();

    } // dinThemeView::decorate()


    /**
     * Build layout content
     * 
     * @return  string  Output string
     */
    protected function buildLayout()
    {

        // build components
        $components = sfConfig::get( 'theme_' . $this->theme . '_components', array() );
        foreach ( $components as $component => $actions )
        {
            foreach ( $actions as $action => $params )
            {
                if ( strpos( $action, '.' ) !== false )
                {
                    $action = substr( $action, 0, strpos( $action, '.' ) );
                }
                $params['vars'] = isset( $params['vars'] ) ? $params['vars'] : array();
                $params['order'] = isset( $params['order'] ) ? $params['order'] : 1;
                $this->setLayoutSlot(
                    $params['layout'], $params['slot'],
                    $this->renderComponent( $component, $action, $params['vars'] ),
                    $params['order']
                );
            }
        }

        // build layouts
        $layouts = sfConfig::get( 'theme_' . $this->theme . '_layouts', array() );
        foreach( $layouts as $layout => $params )
        {
            if ( $layout == 'layout' )
            {
                $base = $params;
                continue;
            }
            $params['order'] = isset( $params['order'] ) ? $params['order'] : 1;
            $params['vars'] = isset( $params['vars'] ) ? $params['vars'] : array();
            $this->setLayoutSlot(
                $params['parent'], $params['slot'],
                $this->renderLayout( $this->layoutPath . $layout . $this->extension, $params['vars'] ),
                $params['order']
            );
        }

        // build base layout
        $base['vars'] = isset( $base['vars'] ) ? $base['vars'] : array();
        return $this->renderLayout( $this->layoutPath . 'layout' . $this->extension, $base['vars'] );

    } // dinThemeView::buildLayout()


    /**
     * Rendering component
     * 
     * @param   string  $moduleName     Module name
     * @param   string  $componentName  Component name
     * @param   array   $params         Component params
     * @return  string  Rendered component content
     */
    public function renderComponent( $moduleName, $componentName, $params )
    {

        $actionName = '_' . $componentName;

        $view = new dinThemePartialView( $this->context, $moduleName, $actionName, '' );
        $view->setPartialVars( $params );

        if ( $retval = $view->getCache() )
        {
            return $retval;
        }

        $allVars = $this->callComponent( $moduleName, $componentName, $params );

        if ( !is_null( $allVars ) )
        {
            // render
            $view->getAttributeHolder()->add( $allVars );
            return $view->render();
        }

    } // dinThemeView::renderComponent()


    /**
     * Calling component
     * 
     * @param   string  $moduleName     Module name
     * @param   string  $componentName  Component name
     * @param   array   $params         Component params
     * @return  array   Component vars
     * @throws  sfInitializationException   If component not exist
     */
    protected function callComponent( $moduleName, $componentName, $params = array() )
    {

        $controller = $this->context->getController();
        if ( !$controller->componentExists( $moduleName, $componentName ) )
        {
            // cannot find component
            throw new sfConfigurationException( sprintf(
                'The component does not exist: "%s", "%s".', $moduleName, $componentName
            ) );
        }
        $componentInstance = $controller->getComponent( $moduleName, $componentName );
        $componentInstance->getVarHolder()->add( $params );

        // load modules config
        require( $this->context->getConfigCache()->checkConfig(
            'modules/' . $moduleName . '/config/module.yml'
        ) );

        // dispatch component
        $componentToRun = 'execute' . ucfirst( $componentName );
        if ( !method_exists( $componentInstance, $componentToRun ) )
        {
            if ( !method_exists( $componentInstance, 'execute' ) )
            {
                // component not found
                throw new sfInitializationException( sprintf(
                    'sfComponent initialization failed for module "%s", component "%s".',
                    $moduleName, $componentName
                ) );
            }
            $componentToRun = 'execute';
        }

        if ( sfConfig::get( 'sf_logging_enabled' ) )
        {
            $this->context->getEventDispatcher()->notify( new sfEvent( null, 'application.log', array(
                sprintf( 'Call "%s->%s()' . '"', $moduleName, $componentToRun )
            ) ) );
        }

        // run component
        if ( sfConfig::get( 'sf_debug' ) && sfConfig::get( 'sf_logging_enabled' ) )
        {
            $timer = sfTimerManager::getTimer(
                sprintf( 'Component "%s/%s"', $moduleName, $componentName )
            );
        }

        $retval = $componentInstance->$componentToRun( $this->context->getRequest() );

        if ( sfConfig::get( 'sf_debug' ) && sfConfig::get( 'sf_logging_enabled' ) )
        {
            $timer->addTime();
        }

        return sfView::NONE == $retval ? null : $componentInstance->getVarHolder()->getAll();

    } // dinThemeView::callComponent()


    /**
     * Render layout
     * 
     * @param   string  $layoutFile     Layout filepath
     * @param   array   $params         Component params
     * @return  string  Rendered layout content
     */
    protected function renderLayout( $layoutFile, $params )
    {

        $attributeHolder = $this->attributeHolder;

        $this->attributeHolder = $this->initializeAttributeHolder( $params );
        $this->attributeHolder->set( 'sf_type', 'layout' );

        $ret = $this->renderFile( $layoutFile );
        $this->attributeHolder = $attributeHolder;

        return $ret;

    } // dinThemeView::renderLayout()


    /**
     * Set slot content for layout
     * 
     * @param   string  $layoutName Layout name
     * @param   string  $slotName   Slot name
     * @param   string  $content    Rendered content
     * @param   integer $order      Order of slot position [optional]
     * @return  void
     */
    public function setLayoutSlot( $layoutName, $slotName, $content, $order = 1 )
    {

        $this->slotHolder[$layoutName][$slotName][$order] = $content;

    } // dinThemeView::setLayoutSlot()


    /**
     * Get slot content for layout
     * 
     * @param   string  $layoutName Layout name
     * @param   string  $slotName   Slot name
     * @return  string  Slot content
     */
    public function getLayoutSlot( $layoutName, $slotName )
    {

        if ( isset( $this->slotHolder[$layoutName][$slotName] ) )
        {
            ksort( $this->slotHolder[$layoutName][$slotName] );
            return implode( '', $this->slotHolder[$layoutName][$slotName] );
        }
        return '';

    } // dinThemeView::getLayoutSlot()

} // dinThemeView

//EOF