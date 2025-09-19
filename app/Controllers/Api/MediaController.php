<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Response;

class MediaController extends Controller
{
    private string $uploadPath;
    private array $allowedMimeTypes;
    private int $maxFileSize;

    public function __construct()
    {
        $this->uploadPath = __DIR__ . '/../../../public/uploads/';
        $this->allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        
        // Create subdirectories
        $subdirs = ['images', 'documents', 'temp'];
        foreach ($subdirs as $subdir) {
            if (!is_dir($this->uploadPath . $subdir)) {
                mkdir($this->uploadPath . $subdir, 0755, true);
            }
        }
    }

    /**
     * Upload single file
     */
    public function upload(Request $request): Response
    {
        try {
            $file = $request->file('file');
            
            if (!$file || !$file->isValid()) {
                return $this->json([
                    'success' => false,
                    'error' => 'No valid file uploaded',
                    'data' => null
                ], 400);
            }
            
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return $this->json([
                    'success' => false,
                    'error' => $validation['message'],
                    'data' => null
                ], 400);
            }
            
            // Generate unique filename
            $extension = pathinfo($file->getClientName(), PATHINFO_EXTENSION);
            $filename = $this->generateUniqueFilename($extension);
            
            // Determine subdirectory
            $subdir = $this->getSubdirectory($file->getMimeType());
            $relativePath = "uploads/{$subdir}/{$filename}";
            $fullPath = $this->uploadPath . $subdir . '/' . $filename;
            
            // Move file
            if (!$file->move($fullPath)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Failed to save file',
                    'data' => null
                ], 500);
            }
            
            $fileInfo = [
                'filename' => $filename,
                'original_name' => $file->getClientName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $relativePath,
                'url' => '/' . $relativePath,
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            
            // If it's an image, get dimensions
            if (strpos($file->getMimeType(), 'image/') === 0) {
                $imageInfo = getimagesize($fullPath);
                if ($imageInfo) {
                    $fileInfo['width'] = $imageInfo[0];
                    $fileInfo['height'] = $imageInfo[1];
                }
            }
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $fileInfo
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(Request $request): Response
    {
        try {
            $files = $request->files();
            
            if (empty($files) || !isset($files['files'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'No files uploaded',
                    'data' => null
                ], 400);
            }
            
            $uploadedFiles = [];
            $errors = [];
            
            // Handle array of files
            $fileArray = $files['files'];
            if (!is_array($fileArray['name'])) {
                $fileArray = [
                    'name' => [$fileArray['name']],
                    'type' => [$fileArray['type']],
                    'tmp_name' => [$fileArray['tmp_name']],
                    'error' => [$fileArray['error']],
                    'size' => [$fileArray['size']]
                ];
            }
            
            for ($i = 0; $i < count($fileArray['name']); $i++) {
                $file = (object) [
                    'name' => $fileArray['name'][$i],
                    'type' => $fileArray['type'][$i],
                    'tmp_name' => $fileArray['tmp_name'][$i],
                    'error' => $fileArray['error'][$i],
                    'size' => $fileArray['size'][$i]
                ];
                
                try {
                    if ($file->error !== UPLOAD_ERR_OK) {
                        $errors[] = "File {$file->name}: Upload error";
                        continue;
                    }
                    
                    // Create a mock UploadedFile-like object
                    $mockFile = new class($file) {
                        private $file;
                        
                        public function __construct($file) {
                            $this->file = $file;
                        }
                        
                        public function isValid(): bool {
                            return $this->file->error === UPLOAD_ERR_OK;
                        }
                        
                        public function getClientName(): string {
                            return $this->file->name;
                        }
                        
                        public function getMimeType(): string {
                            return $this->file->type;
                        }
                        
                        public function getSize(): int {
                            return $this->file->size;
                        }
                        
                        public function getTempPath(): string {
                            return $this->file->tmp_name;
                        }
                        
                        public function move(string $destination): bool {
                            return move_uploaded_file($this->file->tmp_name, $destination);
                        }
                    };
                    
                    // Validate file
                    $validation = $this->validateFile($mockFile);
                    if (!$validation['valid']) {
                        $errors[] = "File {$file->name}: {$validation['message']}";
                        continue;
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file->name, PATHINFO_EXTENSION);
                    $filename = $this->generateUniqueFilename($extension);
                    
                    // Determine subdirectory
                    $subdir = $this->getSubdirectory($file->type);
                    $relativePath = "uploads/{$subdir}/{$filename}";
                    $fullPath = $this->uploadPath . $subdir . '/' . $filename;
                    
                    // Move file
                    if (!$mockFile->move($fullPath)) {
                        $errors[] = "File {$file->name}: Failed to save";
                        continue;
                    }
                    
                    $fileInfo = [
                        'filename' => $filename,
                        'original_name' => $file->name,
                        'mime_type' => $file->type,
                        'size' => $file->size,
                        'path' => $relativePath,
                        'url' => '/' . $relativePath,
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // If it's an image, get dimensions
                    if (strpos($file->type, 'image/') === 0) {
                        $imageInfo = getimagesize($fullPath);
                        if ($imageInfo) {
                            $fileInfo['width'] = $imageInfo[0];
                            $fileInfo['height'] = $imageInfo[1];
                        }
                    }
                    
                    $uploadedFiles[] = $fileInfo;
                    
                } catch (\Exception $e) {
                    $errors[] = "File {$file->name}: {$e->getMessage()}";
                }
            }
            
            $response = [
                'success' => !empty($uploadedFiles),
                'error' => !empty($errors) ? implode(', ', $errors) : null,
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'uploaded_count' => count($uploadedFiles),
                    'failed_count' => count($errors)
                ]
            ];
            
            return $this->json($response);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * List uploaded files
     */
    public function list(Request $request): Response
    {
        try {
            $type = $request->query('type', 'all'); // all, images, documents
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(50, max(5, (int) $request->query('limit', 20)));
            
            $files = [];
            $directories = [];
            
            switch ($type) {
                case 'images':
                    $directories = ['images'];
                    break;
                case 'documents':
                    $directories = ['documents'];
                    break;
                default:
                    $directories = ['images', 'documents'];
            }
            
            foreach ($directories as $dir) {
                $dirPath = $this->uploadPath . $dir;
                if (is_dir($dirPath)) {
                    $dirFiles = scandir($dirPath);
                    foreach ($dirFiles as $file) {
                        if ($file !== '.' && $file !== '..' && is_file($dirPath . '/' . $file)) {
                            $filePath = $dirPath . '/' . $file;
                            $relativePath = "uploads/{$dir}/{$file}";
                            
                            $fileInfo = [
                                'filename' => $file,
                                'path' => $relativePath,
                                'url' => '/' . $relativePath,
                                'size' => filesize($filePath),
                                'mime_type' => mime_content_type($filePath),
                                'modified_at' => date('Y-m-d H:i:s', filemtime($filePath))
                            ];
                            
                            // If it's an image, get dimensions
                            if (strpos($fileInfo['mime_type'], 'image/') === 0) {
                                $imageInfo = getimagesize($filePath);
                                if ($imageInfo) {
                                    $fileInfo['width'] = $imageInfo[0];
                                    $fileInfo['height'] = $imageInfo[1];
                                }
                            }
                            
                            $files[] = $fileInfo;
                        }
                    }
                }
            }
            
            // Sort by modification date (newest first)
            usort($files, function($a, $b) {
                return strtotime($b['modified_at']) - strtotime($a['modified_at']);
            });
            
            // Pagination
            $total = count($files);
            $offset = ($page - 1) * $limit;
            $files = array_slice($files, $offset, $limit);
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => [
                    'files' => $files,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function delete(Request $request): Response
    {
        try {
            $filename = $request->routeParam('filename');
            $subdir = $request->query('subdir', 'images');
            
            if (!$filename) {
                return $this->json([
                    'success' => false,
                    'error' => 'Filename is required',
                    'data' => null
                ], 400);
            }
            
            $filePath = $this->uploadPath . $subdir . '/' . $filename;
            
            if (!file_exists($filePath)) {
                return $this->json([
                    'success' => false,
                    'error' => 'File not found',
                    'data' => null
                ], 404);
            }
            
            if (!unlink($filePath)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Failed to delete file',
                    'data' => null
                ], 500);
            }
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file): array
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'message' => 'File size exceeds limit of ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => 'File type not allowed'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $extension): string
    {
        return uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Get subdirectory based on MIME type
     */
    private function getSubdirectory(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'images';
        }
        
        return 'documents';
    }
}
