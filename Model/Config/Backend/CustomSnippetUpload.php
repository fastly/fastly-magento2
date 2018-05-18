<?php

namespace Fastly\Cdn\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class CustomSnippetUpload extends File
{
    /**
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return ['vcl'];
    }

    /**
     * Save uploaded file before saving config value
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();
        if (!empty($file)) {
            $uploadDir = $this->_getUploadDir();
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $result['file'];
            if ($filename) {
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->setValue('');
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }

    public function _getUploadDir()
    {
        $fieldConfig = $this->getFieldConfig();

        if (!array_key_exists('upload_dir', $fieldConfig)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The base directory to upload file is not specified.')
            );
        }

        if (is_array($fieldConfig['upload_dir'])) {
            $uploadDir = $fieldConfig['upload_dir']['value'];
            if (array_key_exists('scope_info', $fieldConfig['upload_dir'])
                && $fieldConfig['upload_dir']['scope_info']
            ) {
                $uploadDir = $this->_appendScopeInfo($uploadDir);
            }

            if (array_key_exists('config', $fieldConfig['upload_dir'])) {
                $uploadDir = $this->getUploadDirPath($uploadDir);
            }
        } else {
            $uploadDir = (string)$fieldConfig['upload_dir'];
        }

        return $uploadDir;
    }

    public function getUploadDirPath($uploadDir)
    {
        $varDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        return $varDirectory->getAbsolutePath($uploadDir);
    }
}
