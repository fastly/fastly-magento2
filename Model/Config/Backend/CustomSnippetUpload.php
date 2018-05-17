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
        return [];
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
