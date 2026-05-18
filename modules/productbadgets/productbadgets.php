<?php
/**
 * Copyright/License Block
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductBadgets extends Module
{
    public function __construct()
    {
        $this->name = 'productbadgets';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Blinders Group';
        $this->need_instance = 0;
        
        $this->ps_versions_compliancy = [
            'min' => '1.7.8.0', 
            'max' => '1.7.8.99'
        ];
        
        $this->bootstrap = true;

        parent::__construct();

        // Nombre y descripción visibles en el panel de administración
        $this->displayName = $this->l('Product Badgets');
        $this->description = $this->l('Módulo para gestionar y mostrar distintivos (badges) en los productos.');

        $this->confirmUninstall = $this->l('¿Seguro que quieres desinstalar el módulo Product Badgets?');
    }

    /**
     * Método de instalación
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && Configuration::updateValue('PRODUCTBADGETS_LIVE', false);
    }

    /**
     * Método de desinstalación
     */
    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('PRODUCTBADGETS_LIVE');
    }

    /**
     * Hook para cargar estilos y scripts en el Front-Office
     */
    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    /**
     * Panel de configuración en el Back-Office
     */
    public function getContent()
    {
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }
}