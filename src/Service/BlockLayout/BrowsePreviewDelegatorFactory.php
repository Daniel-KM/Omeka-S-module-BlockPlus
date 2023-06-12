<?php declare(strict_types=1);

namespace BlockPlus\Service\BlockLayout;

use BlockPlus\Site\BlockLayout\BrowsePreview;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

class BrowsePreviewDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $services, $name, callable $callback, array $options = null)
    {
        return new BrowsePreview(
            // Callback is the real browsePreview.
            $callback(),
            $services->get('Omeka\HtmlPurifier')
        );
    }
}
