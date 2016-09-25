<?php

namespace Codesleeve\Stapler;

use Codesleeve\Stapler\Interfaces\Validator as ValidatorInterface;

class Validator implements ValidatorInterface
{
    /**
     * Validate the attachment options for an attachment type.
     * A url is required to have either an :id or an :id_partition interpolation.
     *
     * @param array $options
     */
    public function validateOptions(array $options)
    {
        switch ($options['storage']) {
            case 'filesystem':
                $this->validateFilesystemOptions($options);
                break;
            case 's3':
                $this->validateS3Options($options);
                break;
            case 'azure_blob':
                $this->validateAzureBlobOptions($options);
                break;
        }
    }

    /**
     * Validate the attachment options for an attachment type when the storage
     * driver is set to 'filesystem'.
     *
     * @throws Exceptions\InvalidUrlOptionException
     *
     * @param array $options
     */
    protected function validateFilesystemOptions(array $options)
    {
        if (preg_match("/:id\b/", $options['url']) !== 1 && preg_match("/:id_partition\b/", $options['url']) !== 1 && preg_match("/:(secure_)?hash\b/", $options['url']) !== 1) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Url: an id, id_partition, hash, or secure_hash interpolation is required.', 1);
        }
    }

    /**
     * Validate the attachment options for an attachment type when the storage
     * driver is set to 's3'.
     *
     * @throws Exceptions\InvalidUrlOptionException
     *
     * @param array $options
     */
    protected function validateS3Options(array $options)
    {
        if (!$options['s3_object_config']['Bucket']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a bucket is required for s3 storage.', 1);
        }

        if (!$options['s3_client_config']['secret']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a secret is required for s3 storage.', 1);
        }

        if (!$options['s3_client_config']['key']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a key is required for s3 storage.', 1);
        }
    }

    /**
     * Validate the attachment options for an attachment type when the storage
     * driver is set to 'azure_blob'.
     *
     * @throws Exceptions\InvalidUrlOptionException
     *
     * @param array $options
     */
    protected function validateAzureBlobOptions(array $options)
    {
        if (!$options['azure_blob_config']['container']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a container is required for Azure storage.', 1);
        }

        if (!$options['azure_blob_config']['url']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a url is required for Azure storage.', 1);
        }

        if (!$options['azure_blob_config']['name']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a name is required for Azure storage.', 1);
        }

        if (!$options['azure_blob_config']['key']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a key is required for Azure storage.', 1);
        }
    }
}
