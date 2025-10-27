<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpGridProducts\Helpers;

class getTwigEnvironment
{
    protected $module_name;
    protected $context;

    public function __construct($module_name)
    {
        $this->module_name = $module_name;
        $this->context = \Context::getContext();
    }

    protected function load()
    {
        // Base URL
        $baseUrl = $this->context->link->getBaseLink();
        // Percorso moduli Prestashop
        $modulesPath = _PS_MODULE_DIR_;
        // Modulo corrente
        $moduleNamePath = "{$this->module_name}/";
        // Percorso completo modulo
        $modulePath = "{$modulesPath}{$moduleNamePath}";
        // Percorso della cartella views
        $moduleViewsPath = "{$modulePath}views/";
        // Percorso della cartella dei template dei moduli
        $moduleTwigPath = "{$modulePath}views/twig/";
        // Percorso della cartella assets
        $moduleAssetsPath = "{$modulePath}views/assets/";
        // Percorso della cartella css
        $moduleViewsUrl = "{$baseUrl}/modules/{$moduleNamePath}views/";

        // Inizializza il FilesystemLoader e aggiungi un percorso
        // Il primo argomento è il percorso fisico
        // Il secondo è il nome del namespace
        $loader = new \Twig\Loader\FilesystemLoader();
        $loader->addPath($modulesPath, 'Modules');
        $loader->addPath($modulePath, 'Module');

        // Inizializza l'Environment Twig
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('baseUrl', $this->context->link->getBaseLink());
        $twig->addGlobal('modulePath', $this->context->link->getBaseLink() . '/modules/{$moduleNamePath}');
        $twig->addGlobal('moduleViewsPath', $moduleViewsPath);
        $twig->addGlobal('moduleViewsUrl', $moduleViewsUrl);
        if (file_exists($moduleAssetsPath)) {
            $twig->addGlobal('moduleAssetsPath', $moduleAssetsPath);
        }
        if (file_exists($moduleTwigPath)) {
            $twig->addGlobal('moduleTwigPath', $moduleTwigPath);
            $loader->addPath($moduleTwigPath, 'ModuleTwig');
        }

        return $twig;
    }

    public function renderTemplate($path, $params)
    {
        $twig = $this->load();
        $template = $twig->load($path);

        return $template->render($params);
    }
}
