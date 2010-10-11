<?php

/**
 * This file is part of the dinThemePlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

/**
 * A partials view that accept themes system
 * 
 * @package     dinThemePlugin
 * @subpackage  lib.view
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinThemePartialView extends dinThemeView
{

    /**
     * @var object  Template variables holder for partials
     */
    protected $partialVars = array();


    /**
     * Executes any presentation logic for this view
     * 
     * @return  void
     */
    public function execute()
    {
    } // dinThemePartialView::execute()


    /**
     * Setting variables for partial
     * 
     * @param   array   $partialVars    Partial variables
     * @return  void
     */
    public function setPartialVars( array $partialVars )
    {

        $this->partialVars = $partialVars;
        $this->getAttributeHolder()->add( $partialVars );

    } // dinThemePartialView::setPartialVars()


    /**
     * Configures template for this view
     * 
     * @return  void
     */
    public function configure()
    {

        $this->configureHaml();

        $this->setDecorator( false );
        $this->setTemplate( $this->actionName . $this->getExtension() );

        // set theme configuration
        $this->theme = $theme = sfConfig::get( 'app_view_theme', 'default' );
        $app_dir = sfConfig::get( 'sf_app_dir' );
        $this->themePath = sfConfig::get( 'theme_' . $theme . '_path', '/themes/' . $theme );
        $this->modulePath = $app_dir . $this->themePath
            . sfConfig::get( 'theme_' . $theme . '_module_path', '/modules') . '/'
            . $this->moduleName . '/';

        if ( !$this->directory )
        {
            if ( 'global' == $this->moduleName )
            {
                $this->setDirectory(
                    $this->context->getConfiguration()->getDecoratorDir( $this->getTemplate() )
                );
            }
            else
            {
                $this->setDirectory( $this->modulePath );
            }
        }

    } // dinThemePartialView::configure()


    /**
     * Renders the presentation
     * 
     * @return  string  Current template content
     */
    public function render()
    {

        if ( sfConfig::get( 'sf_debug' ) && sfConfig::get( 'sf_logging_enabled' ) )
        {
            $timer = sfTimerManager::getTimer( sprintf(
                'Partial "%s/%s"', $this->moduleName, $this->actionName
            ) );
        }

        if ( $retval = $this->getCache() )
        {
            return $retval;
        }
        else if ( sfConfig::get( 'sf_cache' ) )
        {
            $mainResponse = $this->context->getResponse();
            $responseClass = get_class( $mainResponse );
            $this->context->setResponse(
                $response = new $responseClass(
                    $this->context->getEventDispatcher(),
                    array_merge(
                        $mainResponse->getOptions(),
                        array( 'content_type' => $mainResponse->getContentType() )
                    )
                )
            );
        }

        // execute pre-render check
        $this->preRenderCheck();

        $this->getAttributeHolder()->set( 'sf_type', 'partial' );

        // render template
        $retval = $this->renderFile( $this->getDirectory() . '/' . $this->getTemplate() );

        if ( sfConfig::get( 'sf_cache' ) )
        {
            $retval = $this->viewCache->setPartialCache(
                $this->moduleName, $this->actionName, $this->cacheKey, $retval
            );
            $this->context->setResponse( $mainResponse );
            $mainResponse->merge( $response );
        }

        if ( sfConfig::get( 'sf_debug' ) && sfConfig::get( 'sf_logging_enabled' ) )
        {
            $timer->addTime();
        }

        return $retval;

    } // dinThemePartialView::render()


    /**
     * Get cached partial
     * 
     * @return  string  Cached content
     */
    public function getCache()
    {

        if ( !sfConfig::get( 'sf_cache' ) )
        {
            return null;
        }

        $this->viewCache = $this->context->getViewCacheManager();
        $this->viewCache->registerConfiguration( $this->moduleName );

        $this->cacheKey = $this->viewCache->computeCacheKey( $this->partialVars );
        $retval = $this->viewCache->getPartialCache(
            $this->moduleName, $this->actionName, $this->cacheKey
        );
        return $retval ? $retval : null;

    } // dinThemePartialView::getCache()

} // dinThemePartialView

//EOF