<?php

namespace Codesleeve\Stapler\Factories;

use Codesleeve\Stapler\Attachment as AttachedFile;
use Codesleeve\Stapler\Storage\AzureBlob;
use Codesleeve\Stapler\Storage\Filesystem;
use Codesleeve\Stapler\Storage\S3;
use Codesleeve\Stapler\Stapler;

class Storage
{
    /**
     * Build a storage instance.
     *
     * @param AttachedFile $attachment
     *
     * @return \Codesleeve\Stapler\Interfaces\Storage
     */
    public static function create(AttachedFile $attachment)
    {
        switch ($attachment->storage) {
            case 'filesystem':
                return new Filesystem($attachment);
                break;

            case 's3':
                $s3Client = Stapler::getStorageClientInstance($attachment);

                return new S3($attachment, $s3Client);
                break;

            case 'azure_blob':
                $azureClient = Stapler::getStorageClientInstance($attachment);

                return new AzureBlob($attachment, $azureClient);
                break;

            default:
                return new Filesystem($attachment);
                break;
        }
    }
}
