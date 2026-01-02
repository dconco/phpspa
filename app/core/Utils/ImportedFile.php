<?php

namespace PhpSPA\Core\Utils;

/**
 * Class ImportedFile
 *
 * Represents a file that has been imported into the application.
 * Provides methods and properties to handle file metadata, validation,
 * and processing within the application's workflow.
 *
 * @package PhpSPA\Core\Utils
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @var string $dataUri
 * @var string $contentType
 * @var string $base64Content
 * @var string $filePath
 * @var int $contentLength
 * @see https://phpspa.tech/references/file-import-utility
 */
class ImportedFile
{
   /**
    * The data URI representing the imported file's contents.
    *
    * @var string
    */
   private string $dataUri;

   /**
    * The MIME content type of the imported file.
    *
    * @var string
    */
   private string $contentType;

   /**
    * The base64-encoded content of the imported file.
    *
    * @var string
    */
   private string $base64Content;

   /**
    * The file path where the imported file is stored.
    *
    * @var string
    */
   private string $filePath;

   /**
    * The length of the base64-encoded content.
    *
    * @var int
    */
   private int $contentLength;

   /**
    * Initializes a new instance of the ImportedFile class.
    *
    * @param string $dataUri  The data URI representing the file's contents.
    * @param string $filePath The file system path to the imported file.
    */
   public function __construct (string $dataUri, string $filePath)
   {
      $this->dataUri = $dataUri;
      $this->filePath = $filePath;

      // Parse the data URI
      $parts = explode(',', $dataUri, 2);
      $metadata = explode(';', $parts[0]);

      $this->contentType = substr($metadata[0], 5); // Remove "data:"
      $this->base64Content = $parts[1];
      $this->contentLength = strlen($this->base64Content);
   }

   /**
    * Returns the string representation of the ImportedFile object.
    *
    * This method is automatically called when the object is treated as a string.
    *
    * @return string The string representation of the ImportedFile.
    */
   public function __toString (): string
   {
      return $this->dataUri;
   }

   /**
    * Retrieves the MIME content type of the imported file.
    *
    * @return string The MIME type of the file (e.g., 'image/jpeg', 'application/pdf').
    */
   public function getContentType (): string
   {
      return $this->contentType;
   }

   /**
    * Retrieves the length of the file content in bytes.
    *
    * @return int The size of the file content in bytes.
    */
   public function getContentLength (): int
   {
      return $this->contentLength;
   }

   /**
    * Retrieves the original size of the imported file in bytes.
    *
    * @return int The original file size in bytes.
    */
   public function getOriginalSize (): int
   {
      // Calculate original size from base64
      return (int) ($this->contentLength * 3 / 4 - substr_count($this->base64Content, '='));
   }

   /**
    * Retrieves the location of the imported file.
    *
    * @return string The file location as a string.
    */
   public function getLocation (): string
   {
      return $this->filePath;
   }

   /**
    * Retrieves the filename of the imported file.
    *
    * @return string The name of the file.
    */
   public function getFilename (): string
   {
      return basename($this->filePath);
   }

   /**
    * Retrieves the file extension of the imported file.
    *
    * @return string The file extension as a string.
    */
   public function getExtension (): string
   {
      return pathinfo($this->filePath, PATHINFO_EXTENSION);
   }

   /**
    * Retrieves the raw content of the imported file as a string.
    *
    * @return string The raw file content.
    */
   public function getRawContent (): string
   {
      return base64_decode($this->base64Content);
   }

   /**
    * Retrieves the content of the imported file encoded in Base64 format.
    *
    * @return string The Base64-encoded content of the file.
    */
   public function getBase64Content (): string
   {
      return $this->base64Content;
   }

   /**
    * Determines if the imported file is an image.
    *
    * @return bool Returns true if the file is an image, false otherwise.
    */
   public function isImage (): bool
   {
      return strpos($this->contentType, 'image/') === 0;
   }

   /**
    * Saves the imported file to the specified destination.
    *
    * @param string $destination The path where the file should be saved.
    * @return bool Returns true on success, or false on failure.
    */
   public function saveAs (string $destination): bool
   {
      return file_put_contents($destination, $this->getRawContent()) !== false;
   }
}