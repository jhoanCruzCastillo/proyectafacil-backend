<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Railway Storage Bucket (S3-compatible) u otro S3.
 * Variables en backend/.env:
 *   storage.endpoint / storage.region / storage.bucket / storage.accessKey / storage.secretKey
 * Opcional: storage.forcePathStyle = true  (si las credenciales del bucket piden path-style)
 */
class Storage extends BaseConfig
{
    public string $endpoint = '';
    public string $region = 'auto';
    public string $bucket = '';
    public string $accessKey = '';
    public string $secretKey = '';
    /** true = https://endpoint/bucket/key ; false (Railway nuevo) = https://bucket.endpoint/key */
    public bool $forcePathStyle = false;
    /** Segundos de validez de la URL firmada que ve el frontend (fetch sin Bearer). */
    public int $presignTtlSeconds = 21600; // 6 h
}
