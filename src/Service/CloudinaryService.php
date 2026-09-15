<?php

namespace Drupal\user_crud\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Cloudinary\Cloudinary;


class CloudinaryService {

  /**
   * Cloudinary instance.
   *
   * @var \Cloudinary\Cloudinary
   */
  protected $cloudinary;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $config = $configFactory->get('cloudinary');

    $this->cloudinary = new Cloudinary([
      'cloud' => [
        'cloud_name' => $config->get('cloud_name'),
        'api_key' => $config->get('api_key'),
        'api_secret' => $config->get('api_secret'),
      ],
    ]);
  }

  /**
   * Upload an image to Cloudinary.
   */
  public function uploadImage(string $filePath): string {

    $result = $this->cloudinary
      ->uploadApi()
      ->upload($filePath, [
        'folder' => 'user_crud/products',
      ]);

    return $result['secure_url'];
  }

}