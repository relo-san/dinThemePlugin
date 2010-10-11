<?php

/**
 * This file is part of the dinThemePlugin package.
 * (c) DineCat, 2010 http://dinecat.com/
 * 
 * For the full copyright and license information, please view the LICENSE file,
 * that was distributed with this package, or see http://www.dinecat.com/din/license.html
 */

require_once dirname(__FILE__) . '/../vendor/phphaml/includes/haml/HamlParser.class.php';

/**
 * A view that accept themes system
 * 
 * @package     dinThemePlugin
 * @subpackage  lib.view
 * @author      Nicolay N. Zyk <relo.san@gmail.com>
 */
class dinHamlView extends sfPHPView
{

    /**
     * @var Parser object
     */
    protected $parser;


    protected $isHaml = true;


    /**
     * Configures template
     * 
     * @return  void
     */
    public function configureHaml()
    {

        if ( sfConfig::get( 'mod_' . $this->moduleName . '_use_haml', true ) )
        {
            if ( !is_dir( $dir = sfConfig::get( 'sf_template_cache_dir' ) ) )
            {
                @mkdir( $dir, 0777, true );
            }
            $this->parser = new HamlParser( false, sfConfig::get( 'sf_template_cache_dir' ) );
            $this->parser->addCustomBlock( 'slot', 'end_slot' );
            $this->sTranslate = 'I18n::__';
            $this->parser->registerBlock( 'Tag::js', 'javascript' );

            $this->parser->registerBlock( 'trim', 'php' );
            $this->extension = '.haml';
            return;
        }
        $this->isHaml = false;
        return;

    } // dinHamlView::configureHaml()


    /**
     * Load core helpers
     * 
     * @return  void
     */
    protected function loadCoreAndStandardHelpers()
    {

        static $coreHelpersLoaded = 0;

        if ( $coreHelpersLoaded )
        {
            return;
        }

        $coreHelpersLoaded = 1;

        $helpers = array_unique( array_merge( array( 'Escaping' ), sfConfig::get( 'sf_standard_helpers' ) ) );
        $this->context->getConfiguration()->loadHelpers( $helpers );

    } // dinHamlView::loadCoreAndStandardHelpers()


    /**
     * Rendering file
     * 
     * @param   string  $file   Template filename
     * @return  void
     */
    protected function renderFile( $file )
    {

        if ( !$this->isHaml )
        {
            return parent::renderFile( $file );
        }

        if ( sfConfig::get( 'sf_logging_enabled', false ) )
        {

            $this->dispatcher->notify(
                new sfEvent( $this, 'application.log', array( sprintf( 'Render "%s"', $file ) ) )
            );
        }
        $this->loadCoreAndStandardHelpers();

        $this->parser->setFile( $file );
        $this->parser->append( $this->attributeHolder->toArray() );

        return $this->parser->render();

    } // dinHamlView::renderFile()

} // dinHamlView

//EOF