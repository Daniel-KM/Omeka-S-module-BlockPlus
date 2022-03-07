<?php declare(strict_types=1);

namespace BlockPlus;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Omeka\Module\Exception\ModuleCannotInstallException;

/**
 * BlockPlus
 *
 * @copyright Daniel Berthereau, 2018-2021
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    protected function preInstall(): void
    {
        $js = __DIR__ . '/asset/vendor/ThumbnailGridExpandingPreview/js/grid.js';
        if (!file_exists($js)) {
            $services = $this->getServiceLocator();
            $t = $services->get('MvcTranslator');
            throw new \Omeka\Module\Exception\ModuleCannotInstallException(
                sprintf(
                    $t->translate('The library "%s" should be installed.'), // @translate
                    'javascript'
                ) . ' '
                . $t->translate('See module’s installation documentation.')); // @translate
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\Page',
            'view.edit.before',
            [$this, 'handleSitePageEditBefore']
        );
        $sharedEventManager->attach(
            \Omeka\Stdlib\HtmlPurifier::class,
            'htmlpurifier_config',
            [$this, 'handleHtmlPurifier']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
    }

    public function handleSitePageEditBefore(Event $event): void
    {
        $view = $event->getTarget();
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/block-plus-admin.css', 'BlockPlus'));
    }

    public function handleHtmlPurifier(Event $event): void
    {
        // CKEditor footnotes uses `<section class="footnotes">` and some other
        // elements and attributes, but they are not in the default config, designed for html 4.
        // The same for HTML Purifier, that is based on html 4, and won't be
        // updated to support html 5.
        // @see https://github.com/ezyang/htmlpurifier/issues/160

        /** @var \HTMLPurifier_Config $config */
        $config = $event->getParam('config');

        $config->set('Attr.EnableID', true);
        $config->set('HTML.AllowedAttributes', [
            'a.id',
            'a.rel',
            'a.href',
            'a.target',
            'li.id',
            'li.data-footnote-id',
            'section.class',
            'sup.data-footnote-id',
        ]);

        $config->set('HTML.TargetBlank', true);

        $def = $config->getHTMLDefinition(true);

        $def->addElement('article', 'Block', 'Flow', 'Common');
        $def->addElement('section', 'Block', 'Flow', 'Common');
        $def->addElement('header', 'Block', 'Flow', 'Common');
        $def->addElement('footer', 'Block', 'Flow', 'Common');

        $def->addAttribute('sup', 'data-footnote-id', 'ID');
        // This is the same id than sup, but Html Purifier ID should be unique
        // among all the submitted html ids, so use Class.
        $def->addAttribute('li', 'data-footnote-id', 'Class');

        $def->addAttribute('a', 'target', new \HTMLPurifier_AttrDef_Enum(['_blank', '_self', '_target', '_top']));

        $event->setParam('config', $config);
    }
}
