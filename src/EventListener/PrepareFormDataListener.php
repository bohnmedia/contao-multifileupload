<?php

namespace BohnMedia\ContaoMultifileuploadBundle\EventListener;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\FilesModel;
use Contao\Dbafs;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PrepareFormDataListener
{
    private string $projectDir;
    private string $tmpDir;
    private SessionInterface $session;

    public function __construct(string $projectDir, string $tmpDir, SessionInterface $session)
    {
        $this->projectDir = $projectDir;
        $this->tmpDir = $tmpDir;
        $this->session = $session;
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
     * Move file from temp to target folder and return files model
     *
     * @param  string $tmpFile
     * @param  string $dstFile
     * @return FilesModel
     */
    private function moveTo(string $filename, string $dstDir): FilesModel
    {
        $strTmpDir = $this->getTmpDir();
        $strTmpPath = Path::join($strTmpDir, $filename);
        $strDstPath = Path::join($dstDir, $filename);
        $strAbsDstPath = Path::join($this->projectDir, $strDstPath);

        if (is_file($strAbsDstPath)) unlink($strAbsDstPath);
        rename($strTmpPath, Path::join($this->projectDir, $strDstPath));

        $objFile = Dbafs::addResource($strDstPath);

        return $objFile;
    }

    /**
     * Process submitted data of form field and return array with UUIDs
     *
     * @param  mixed $objFormField
     * @param  mixed $strFilenames
     * @return array
     */
    public function processFormField(FormFieldModel $objFormField, string $strFilenames): array
    {
        $arrFilenames = $strFilenames ? explode(',', $strFilenames) : [];

        // Return empty array when upload directory was not set
        if (!$objFormField->storeFile || !$objFormField->uploadFolder) {
            return [];
        }

        $objDstDir = FilesModel::findByUuid($objFormField->uploadFolder);

        // Throw exception when upload directory was not found
        if (!$objDstDir) {
            throw new \Exception(
                sprintf('Upload directory with UUID %s not found', $objFormField->uploadFolder)
            );
        }

        // Move files and get array with files
        $arrFiles = array_map(
            fn ($filename) => $this->moveTo($filename, $objDstDir->path),
            $arrFilenames
        );

        // Get array with UUIDs
        $arrUuid = array_map(fn ($file) => $file->uuid, $arrFiles);

        return $arrUuid;
    }

    public function __invoke(array &$submittedData, array $labels, array $fields, Form $form): void
    {
        // Find multiupload form fields in submitted form
        $formFields = FormFieldModel::findBy(
            ['pid=?', 'type=?', 'storeFile=?', 'invisible=?'],
            [$form->id, 'multiupload', '1', '']
        );

        if ($formFields) {
            // Move files from temp to target folder and get uuids
            foreach ($formFields as $formField) {
                $arrUuid = $this->processFormField($formField, $submittedData[$formField->name] ?? '');
                $submittedData[$formField->name] = $arrUuid;
            }
        }
    }
}
