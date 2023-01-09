<?php declare(strict_types=1);

namespace BlockPlus\Service\Form;

use BlockPlus\Form\PageMetadataFieldset;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageMetadataFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $siteSettings = $services->get('Omeka\Settings\Site');
        $fieldset = new PageMetadataFieldset();
        return $fieldset
            ->setPageTypes($siteSettings->get('blockplus_page_types', []));
    }
}
