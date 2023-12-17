<?php

namespace BohnMedia\ContaoMultifileuploadBundle\EventListener;

use Contao\PageRegular;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\Template;

class GeneratePageListener
{
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/contaomultifileupload/multifileupload.css';
        $GLOBALS['TL_BODY'][] = Template::generateScriptTag('bundles/contaomultifileupload/multifileupload.js', false, null);
    }
}