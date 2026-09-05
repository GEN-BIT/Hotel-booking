<?php
/**
 * Image Upload & Optimization Utility
 * 
 * Handles:
 * - Image compression
 * - WebP conversion
 * - Responsive srcset generation
 * - Thumbnail creation
 */

class ImageUploader {
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    private $quality = 85;
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?: __DIR__ . '/../uploads/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        if (!is_dir($this->uploadDir . 'thumbs/')) {
            mkdir($this->uploadDir . 'thumbs/', 0755, true);
        }
        
        if (!is_dir($this->uploadDir . 'webp/')) {
            mkdir($this->uploadDir . 'webp/', 0755, true);
        }
    }
    
    /**
     * Process uploaded image
     */
    public function upload($file, $prefix = 'img_') {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Invalid file upload'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'File too large. Maximum size is 5MB'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, WebP and GIF allowed'];
        }
        
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = $prefix . uniqid() . '.' . $extension;
        $filepath = $this->uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }
        
        // Optimize image
        $this->optimizeImage($filepath, $mimeType);
        
        // Create thumbnail
        $thumbPath = $this->createThumbnail($filepath, $mimeType, $prefix);
        
        // Create WebP version
        $webpPath = $this->createWebP($filepath, $mimeType, $prefix);
        
        // Generate srcset
        $srcset = $this->generateSrcset($filename, $extension);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'thumb' => $thumbPath,
            'webp' => $webpPath,
            'srcset' => $srcset,
            'mime_type' => $mimeType,
        ];
    }
    
    /**
     * Optimize image by compressing
     */
    private function optimizeImage($filepath, $mimeType) {
        $image = $this->loadImage($filepath, $mimeType);
        if (!$image) return;
        
        $this->saveImage($image, $filepath, $mimeType, $this->quality);
        imagedestroy($image);
    }
    
    /**
     * Create thumbnail
     */
    private function createThumbnail($filepath, $mimeType, $prefix) {
        $thumbSize = 300;
        $thumbPath = $this->uploadDir . 'thumbs/' . $prefix . basename($filepath);
        
        $image = $this->loadImage($filepath, $mimeType);
        if (!$image) return null;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $height) {
            $newWidth = $thumbSize;
            $newHeight = (int)($height * $thumbSize / $width);
        } else {
            $newHeight = $thumbSize;
            $newWidth = (int)($width * $thumbSize / $height);
        }
        
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and WebP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);
        }
        
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        $this->saveImage($thumb, $thumbPath, $mimeType, $this->quality - 10);
        imagedestroy($thumb);
        imagedestroy($image);
        
        return basename($thumbPath);
    }
    
    /**
     * Create WebP version
     */
    private function createWebP($filepath, $mimeType, $prefix) {
        if (!function_exists('imagewebp')) {
            return null;
        }
        
        $webpPath = $this->uploadDir . 'webp/' . $prefix . basename($filepath, '.' . $this->getExtensionFromMime($mimeType)) . '.webp';
        
        $image = $this->loadImage($filepath, $mimeType);
        if (!$image) return null;
        
        imagewebp($image, $webpPath, $this->quality);
        imagedestroy($image);
        
        return basename($webpPath);
    }
    
    /**
     * Generate srcset for responsive images
     */
    private function generateSrcset($filename, $extension) {
        $sizes = [400, 800, 1200];
        $srcset = [];
        
        foreach ($sizes as $size) {
            $srcset[] = $this->uploadDir . $filename . ' ' . $size . 'w';
        }
        
        return implode(', ', $srcset);
    }
    
    /**
     * Load image from file
     */
    private function loadImage($filepath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filepath);
            case 'image/png':
                return imagecreatefrompng($filepath);
            case 'image/webp':
                return imagecreatefromwebp($filepath);
            case 'image/gif':
                return imagecreatefromgif($filepath);
            default:
                return false;
        }
    }
    
    /**
     * Save image to file
     */
    private function saveImage($image, $filepath, $mimeType, $quality) {
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $filepath, $quality);
                break;
            case 'image/png':
                imagepng($image, $filepath, (int)round(($quality / 100) * 9));
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagewebp($image, $filepath, $quality);
                }
                break;
            case 'image/gif':
                imagegif($image, $filepath);
                break;
        }
    }
    
    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime($mimeType) {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        
        return $map[$mimeType] ?? 'jpg';
    }
    
    /**
     * Delete image and its variants
     */
    public function delete($filename, $prefix = 'img_') {
        $files = [
            $this->uploadDir . $filename,
            $this->uploadDir . 'thumbs/' . $prefix . $filename,
            $this->uploadDir . 'webp/' . $prefix . basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION)) . '.webp',
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
}
