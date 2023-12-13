<?php

namespace BohnMedia\ContaoMultifileuploadBundle\Widget\Frontend;

use Contao\Widget;

class MultiuploadField extends Widget
{
    protected $blnSubmitInput = true;
    protected $blnForAttribute = true;
    protected $strTemplate = 'form_multiupload_field';
    protected $strPrefix = 'widget widget-multiupload-field';

    public function generate(): string
    {
        return '';
    }
}