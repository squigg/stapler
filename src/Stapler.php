<?php

namespace Codesleeve\Stapler;

use Beberlei\AzureBlobStorage\BlobClient;
use Codesleeve\Stapler\Interfaces\Attachment as AttachmentInterface;
use Codesleeve\Stapler\Interfaces\Config as ConfigInterface;
use Codesleeve\Stapler\File\Image\Resizer;
use Aws\S3\S3Client;

/**
 * Easy file attachment management for Eloquent (Laravel 4).
 *
 * Credits to the guys at thoughtbot for creating the
 * paperclip plugin (rails) from which this package is inspired.
 * https://github.com/thoughtbot/paperclip
 *
 * @version v1.1.1
 *
 * @author Travis Bennett <tandrewbennett@hotmail.com>
 *
 * @link
 */
class Stapler
{
    /**
     * Holds the hash value for the current STAPLER_NULL constant.
     *
     * @var string
     */
    protected static $staplerNull;

    /**
     * An instance of the interpolator class for processing interpolations.
     *
     * @var \Codesleeve\Stapler\Interfaces\Interpolator
     */
    protected static $interpolator;

    /**
     * An instance of the validator class for validating attachment configurations.
     *
     * @var \Codesleeve\Stapler\Interfaces\Validator
     */
    protected static $validator;

    /**
     * An instance of the resizer class for processing images.
     *
     * @var \Codesleeve\Stapler\Interfaces\Resizer
     */
    protected static $resizer;

    /**
     * A configuration object instance.
     *
     * @var ConfigInterface
     */
    protected static $config;

    /**
     * An array of image processing libs.
     * Each time an new image processing lib (GD, Gmagick, or Imagick)
     * is used, we'll cache it here in order to prevent
     * memory leaks.
     *
     * @var array
     */
    protected static $imageProcessors = [];

    /**
     * A key value store of storage clients.
     * Because storage clients are model-attachment specific, each
     * time we create a new one (for a given model/attachment combo)
     * we'll need to cache it here in order to prevent
     * memory leaks.
     *
     * @var array
     */
    protected static $storageClients = [];

    /**
     * Boot up stapler.
     * Here, we'll register any needed constants and prime up
     * the settings required by the package.
     */
    public static function boot()
    {
        static::$staplerNull = sha1(time());

        if (!defined('STAPLER_NULL')) {
            define('STAPLER_NULL', static::$staplerNull);
        }
    }

    /**
     * Return a shared of instance of the Interpolator class.
     * If there's currently no instance in memory we'll create one
     * and then hang it as a property on this class.
     *
     * @return \Codesleeve\Stapler\Interfaces\Interpolator
     */
    public static function getInterpolatorInstance()
    {
        if (static::$interpolator === null) {
            $className = static::$config->get('bindings.interpolator');
            static::$interpolator = new $className();
        }

        return static::$interpolator;
    }

    /**
     * Return a shared of instance of the Validator class.
     * If there's currently no instance in memory we'll create one
     * and then hang it as a property on this class.
     *
     * @return \Codesleeve\Stapler\Interfaces\Validator
     */
    public static function getValidatorInstance()
    {
        if (static::$validator === null) {
            $className = static::$config->get('bindings.validator');
            static::$validator = new $className();
        }

        return static::$validator;
    }

    /**
     * Return a resizer object instance.
     *
     * @param string $type
     *
     * @return \Codesleeve\Stapler\Interfaces\Resizer
     */
    public static function getResizerInstance($type)
    {
        $imagineInstance = static::getImagineInstance($type);

        if (static::$resizer === null) {
            $className = static::$config->get('bindings.resizer');
            static::$resizer = new $className($imagineInstance);
        } else {
            static::$resizer->setImagine($imagineInstance);
        }

        return static::$resizer;
    }

    /**
     * Return an instance of Imagine interface.
     *
     * @param string $type
     *
     * @return \Imagine\Image\ImagineInterface
     */
    public static function getImagineInstance($type)
    {
        if (!isset(static::$imageProcessors[$type])) {
            static::$imageProcessors[$type] = new $type();
        }

        return static::$imageProcessors[$type];
    }

    /**
     * Create a unique key for this storage client and model
     * This is used for caching the clients
     *
     * @param AttachmentInterface $attachedFile
     *
     * @return string
     */
    protected static function createStorageClientKey(AttachmentInterface $attachedFile) {



    }

    /**
     * Return an S3Client object for a specific attachment type.
     *
     * @param AttachmentInterface $attachedFile
     *
     * @return S3Client
     */
    public static function getS3ClientInstance(AttachmentInterface $attachedFile)
    {
        return static::getStorageClientInstance($attachedFile);
    }

    /**
     * Return an Azure Blob Client object for a specific attachment type.
     *
     * @param AttachmentInterface $attachedFile
     *
     * @return BlobClient
     */
    public static function getAzureBlobClientInstance(AttachmentInterface $attachedFile)
    {
        return static::getStorageClientInstance($attachedFile);
    }

    /**
     * Return a StorageClient object for a specific attachment type.
     * If no instance has been defined yet we'll buld one and then
     * cache it on the storageClients property (for the current request only).
     *
     * @param AttachmentInterface $attachedFile
     *
     * @return S3Client|BlobClient
     */
    public static function getStorageClientInstance(AttachmentInterface $attachedFile)
    {
        $modelName = $attachedFile->getInstanceClass();
        $attachmentName = $attachedFile->getConfig()->name;
        $storageClient = $attachedFile->getConfig()->storage;
        $key = "$storageClient.$modelName.$attachmentName";

        if (array_key_exists($key, static::$storageClients)) {
            return static::$storageClients[$key];
        }

        static::$storageClients[$key] = static::buildStorageClient($attachedFile);

        return static::$storageClients[$key];
    }

    /**
     * Return a configuration object instance.
     * If no instance is currently set, we'll return an instance
     * of Codesleeve\Stapler\Config\NativeConfig.
     *
     * @return ConfigInterface
     */
    public static function getConfigInstance()
    {
        if (!static::$config) {
            static::$config = new Config\NativeConfig();
        }

        return static::$config;
    }

    /**
     * Set the configuration object instance.
     *
     * @param ConfigInterface $config
     */
    public static function setConfigInstance(ConfigInterface $config)
    {
        static::$config = $config;
    }

    /**
     * Build an S3Client instance using the information defined in
     * this class's attachedFile object.
     *
     * @param AttachmentInterface $attachedFile
     * @return S3Client|BlobClient
     * @throws Exceptions\InvalidAttachmentConfigurationException
     */
    protected static function buildStorageClient(AttachmentInterface $attachedFile)
    {
        $storageClient = $attachedFile->getConfig()->storage;

        if ($storageClient == 's3') {
            return S3Client::factory($attachedFile->s3_client_config);
        }

        if ($storageClient == 'azure_blob') {
            $azureConfig = $attachedFile->azure_blob_config;
            return new BlobClient($azureConfig['url'],$azureConfig['name'],$azureConfig['key']);
        }

        throw new Exceptions\InvalidAttachmentConfigurationException('Unknown storage client: ' . $storageClient);

    }
}
