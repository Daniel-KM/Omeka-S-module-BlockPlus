<?php declare(strict_types=1);

namespace BlockPlusTest\Form;

use BlockPlus\Form\SettingsFieldset;
use BlockPlus\Form\SiteSettingsFieldset;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * The html of the page models may contain double quotes, for example a class.
 *
 * @see https://gitlab.com/Daniel-KM/Omeka-S-module-BlockPlus/-/work_items/17
 */
class PageModelsSettingTest extends AbstractHttpControllerTestCase
{
    protected function pageModels(): array
    {
        // Like a page model saved from a page.
        return [
            'highlight' => [
                'o:label' => 'Highlight',
                'o:block' => [
                    [
                        'o:layout' => 'html',
                        'o:data' => [
                            'html' => '<h2>Heading</h2><div class="highlight-description-text"><p>Text</p></div>',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function fieldsetProvider(): array
    {
        return [
            'main settings' => [SettingsFieldset::class],
            'site settings' => [SiteSettingsFieldset::class],
        ];
    }

    /**
     * @dataProvider fieldsetProvider
     */
    public function testHtmlWithDoubleQuotesIsKept(string $fieldsetClass): void
    {
        $messenger = new \Omeka\Mvc\Controller\Plugin\Messenger();
        $messenger->clear();

        $fieldset = $this->getApplicationServiceLocator()->get('FormElementManager')->get($fieldsetClass);
        /** @var \Common\Form\Element\IniTextarea $element */
        $element = $fieldset->get('blockplus_page_models');

        // Display of the stored setting.
        $element->setValue($this->pageModels());
        $string = $element->getValue();
        $this->assertIsString($string);
        $this->assertStringContainsString('class="highlight-description-text"', $string);
        $this->assertSame([], $messenger->get());

        // Submission of the displayed string.
        $this->assertTrue($element->validateIni($string));
        $this->assertSame($this->pageModels(), $element->filterStringToArray($string));
    }
}
