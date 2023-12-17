<?php

namespace BohnMedia\ContaoMultifileuploadBundle\Widget\Frontend;

use Contao\Widget;
use Contao\System;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MultiuploadField extends Widget
{
    protected RequestStack $requestStack;
    protected string $projectDir;
    protected string $tmpDir;
    protected SessionInterface $session;

    protected $blnSubmitInput = true;
    protected $blnForAttribute = true;
    protected $strTemplate = 'form_multiupload_field';
    protected $strPrefix = 'widget widget-multiupload';

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $container = System::getContainer();
        $this->requestStack = $container->get('request_stack');
        $this->projectDir = $container->getParameter('kernel.project_dir');
        $this->tmpDir = $container->getParameter('bohnmedia_multifileupload.tmp_dir');
        $this->session = $container->get('session');
    }

    /**
     * Get filename from request
     *
     * @return string
     */
    private function getFilename(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return basename($request->query->get('filename'));
    }

    /**
     * Get temporary directory for current session
     *
     * @return string
     */
    public function getTmpDir(): string
    {
        return Path::join($this->projectDir, $this->tmpDir, 'contao-multiupload', $this->session->getId());
    }

    /**
     * Get path to temporary file
     *
     * @return string
     */
    public function getTmpPath(): string
    {
        return Path::join($this->getTmpDir(), $this->getFilename());
    }

    private function onPost()
    {
        $strTmpDir = $this->getTmpDir();
        $strTmpPath = $this->getTmpPath();

        // Create temporary directory if not exists
        if (!is_dir($strTmpDir)) mkdir($strTmpDir, 0755, true);

        $success = move_uploaded_file($_FILES['file']['tmp_name'], $strTmpPath);

        // Throw exception when move_uploaded_file() fails
        if (!$success) {
            throw new \Exception('File upload failed');
        }

        exit();
    }

    private function onDelete()
    {
        $strTmpPath = $this->getTmpPath();
        if (file_exists($strTmpPath)) unlink($strTmpPath);
        exit();
    }

    public function parse($attributes = null): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->isXmlHttpRequest() && $request->query->get('id') === 'ctrl_' . $this->id) {

            switch ($request->getMethod()) {
                case 'POST':
                    $this->onPost();
                    break;
                case 'POST':
                    $this->onDelete();
                    break;
            }
        }

        return parent::parse($attributes);
    }

    public function generate(): string
    {
        return '';
    }
}
