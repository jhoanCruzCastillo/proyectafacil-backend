<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

// Valores desde backend/.env (cloudinary.cloudName / cloudinary.apiKey / cloudinary.apiSecret) —
// BaseConfig los resuelve automáticamente por convención ClassName.propertyName.
class Cloudinary extends BaseConfig
{
    public string $cloudName = '';
    public string $apiKey = '';
    public string $apiSecret = '';
}
