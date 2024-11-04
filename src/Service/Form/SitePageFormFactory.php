<?php declare(strict_types=1);

namespace BlockPlus\Service\Form;

use BlockPlus\Form\SitePageForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SitePageFormFactory implements FactoryInterface
{
    /**
     * Override site page form factory, that does not trigger any events.
     * @see \Omeka\Form\SitePageForm
     * @see \Omeka\Service\Form\SitePageFormFactory
     *
     * @var \Omeka\Site\Theme\Theme $theme
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $theme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();

        $form = new SitePageForm(null, $options ?? []);
        $form
            ->setCurrentTheme($theme);
        return $form;
    }
}
