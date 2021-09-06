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
 * @copyright Daniel Berthereau, 2018-2020
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
            throw new ModuleCannotInstallException(
                sprintf(
                    $t->translate('The library "%s" should be installed.'), // @translate
                    'javascript'
                ) . ' '
                . $t->translate('See module’s installation documentation.')); // @translate
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // Order blocks alphabetically (translated), except html.
        $sharedEventManager->attach(
            \Omeka\Site\BlockLayout\Manager::class,
            'service.registered_names',
            [$this, 'handleRegisteredNamesBlockLayout']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            [$this, 'handleSiteSettingsFilters']
        );
    }

    public function handleSiteSettings(Event $event): void
    {
        parent::handleSiteSettings($event);

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');

        $fieldset = $event
            ->getTarget()
            ->get('blockplus');

        $pageTypes = $settings->get('blockplus_page_types') ?: [];
        $value = '';
        foreach ($pageTypes as $name => $label) {
            $value .= $name . ' = ' . $label . "\n";
        }
        $fieldset
            ->get('blockplus_page_types')
            ->setValue($value);
    }

    public function handleSiteSettingsFilters(Event $event): void
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->get('blockplus')
            ->add([
                'name' => 'blockplus_page_types',
                'required' => false,
                'filters' => [
                    [
                        'name' => \Laminas\Filter\Callback::class,
                        'options' => [
                            'callback' => [$this, 'stringToKeyValuesPlusDefault'],
                        ],
                    ],
                ],
            ])
        ;
    }

    public function stringToKeyValuesPlusDefault($string)
    {
        $result = [];
        $list = $this->stringToList($string);
        foreach ($list as $keyValue) {
            list($key, $value) = array_map('trim', explode('=', $keyValue, 2));
            if ($key !== '') {
                $result[$key] = mb_strlen($value) ? $value : $key;
            }
        }

        $defaults = [
            'home' => 'Home', // @translate
            'exhibit' => 'Exhibit', // @translate
            'exhibit_page' => 'Exhibit page', // @translate
            'simple' => 'Simple page', // @translate
        ];

        return $result + $defaults;
    }

    public function handleRegisteredNamesBlockLayout(Event $event): void
    {
        $services = $this->getServiceLocator();
        $manager = $services->get('Omeka\BlockLayoutManager');
        $translator = $services->get('MvcTranslator');
        $registeredNames = $event->getParam('registered_names');

        $result = [];
        foreach ($registeredNames as $registeredName) {
            $result[$registeredName] = $translator->translate($manager->get($registeredName)->getLabel());
        }
        natcasesort($result);

        // Keep configured names prepended.
        // TODO Upgrade block list for Omeka v3.1.
        $prepended = $services->get('Config')['block_layouts']['sorted_names'] ?? [];
        $result = array_keys(array_flip($prepended) + $result);

        $event->setParam('registered_names', $result);
    }
}
