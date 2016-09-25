<?php

namespace Codesleeve\Stapler\Storage;

use Beberlei\AzureBlobStorage\BlobClient;
use Codesleeve\Stapler\Attachment;
use Codesleeve\Stapler\Interfaces\Storage as StorageInterface;

class AzureBlob implements StorageInterface
{

    /**
     * The current attachedFile object being processed.
     *
     * @var \Codesleeve\Stapler\Attachment
     */
    public $attachedFile;

    /**
     * The Azure Blob Client instance.
     *
     * @var BlobClient
     */
    protected $blobClient;

    /**
     * Boolean flag indicating if this attachment's container currently exists.
     *
     * @var array
     */
    protected $containerExists = false;

    /**
     * Constructor method.
     *
     * @param Attachment $attachedFile
     * @param BlobClient $blobClient
     */
    public function __construct(Attachment $attachedFile, BlobClient $blobClient)
    {
        $this->attachedFile = $attachedFile;
        $this->blobClient = $blobClient;
    }

    /**
     * Return the url for a file upload.
     *
     * @param string $styleName
     *
     * @return string
     */
    public function url($styleName)
    {
        return $this->blobClient->getBaseUrl() . '/' . $this->attachedFile->azure_blob_config['container'] . $this->path($styleName);
    }

    /**
     * Return the key the uploaded file object is stored under within a bucket.
     *
     * @param string $styleName
     *
     * @return string
     */
    public function path($styleName)
    {
        return $this->attachedFile->getInterpolator()
                                  ->interpolate($this->attachedFile->path, $this->attachedFile, $styleName);
    }

    /**
     * Remove an attached file.
     *
     * @param array $filePaths
     */
    public function remove(array $filePaths)
    {
        foreach ($filePaths as $filePath) {
            $this->blobClient->deleteBlob($this->attachedFile->azure_blob_config['container'], $filePath);
        }
    }

    /**
     * Move an uploaded file to it's intended destination.
     *
     * @param string $file
     * @param string $filePath
     */
    public function move($file, $filePath)
    {
        $this->ensureContainerExists($this->attachedFile->azure_blob_config['container']);
        $this->blobClient->putBlob($this->attachedFile->azure_blob_config['container'], $filePath, $file);

        @unlink($file);
    }

    /**
     * Return an array of paths (bucket keys) for an attachment.
     * There will be one path for each of the attachmetn's styles.
     *
     * @param  $filePaths
     *
     * @return array
     */
    protected function getKeys($filePaths)
    {
        $keys = [];

        foreach ($filePaths as $filePath) {
            $keys[] = ['Key' => $filePath];
        }

        return $keys;
    }

    /**
     * Ensure that a given Azure container exists.
     *
     * @param string $bucketName
     */
    protected function ensureContainerExists($bucketName)
    {
        if (!$this->containerExists) {
            $this->buildContainer($bucketName);
        }
    }

    /**
     * Attempt to build a bucket (if it doesn't already exist).
     *
     * @param string $containerName
     */
    protected function buildContainer($containerName)
    {
        if (!$this->blobClient->containerExists($containerName)) {
            $this->blobClient->createContainer($containerName);
        }

        $this->containerExists = true;
    }
}
