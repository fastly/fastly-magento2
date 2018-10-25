<?php

namespace Fastly\Cdn\Model;

use Magento\Framework\Model\AbstractModel;
use Fastly\Cdn\Model\ResourceModel\Manifest as ManifestModel;

/**
 * Class Manifest
 *
 * @package Inchoo\ProductRelation\Model
 */
class Manifest extends AbstractModel
{

    public function _construct()
    {
        $this->_init(ManifestModel::class);
    }

    /**
     * @param $id
     * @return $this
     */
    public function setManifestId($id)
    {
        return $this->setData('manifest_id', $id);
    }

    /**
     * @param $name
     * @return $this
     */
    public function setManifestName($name)
    {
        return $this->setData('manifest_name', $name);
    }

    /**
     * @param $version
     * @return $this
     */
    public function setManifestVersion($version)
    {
        return $this->setData('manifest_version', $version);
    }

    /**
     * @param $content
     * @return $this
     */
    public function setManifestContent($content)
    {
        return $this->setData('manifest_content', $content);
    }

    public function getManifestId($id)
    {
        return $this->getData('manifest_id', $id);
    }
}
