<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploader
{
    private SluggerInterface $slugger;
    // « $uploadsDirectory » est définit dans « services.yaml »
    private string $uploadsDirectory;
    private Filesystem $filesystem;

    public function __construct(SluggerInterface $slugger, string $uploadsDirectory, Filesystem $filesystem)
    {
        $this->slugger = $slugger;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->filesystem = $filesystem;
    }

    /**
     * Upload a file and return its filename and filepath.
     * 
     * @param UploadedFile $file The uploaded file.
     * @return array{fileName: string, filePath: string}
     */
    public function upload(UploadedFile $file): array
    {
        $filename = $this->generateUniqueFileName($file);
        
        try {
            $this->filesystem->copy($file, $this->uploadsDirectory . $filename);
        } catch (FileException $fileException) {
            throw $fileException;
        }

        return [
            'fileName' => $filename,
            'filePath' => $this->uploadsDirectory
        ];
    }

    /**
     * Generate an unique filename for the uploaded file.
     * 
     * @param UploadedFile $file The uploaded file.
     * @return string The unique slugged filename.
     */
    public function generateUniqueFileName(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalFilenameSlugged = $this->slugger->slug(strtolower($originalFilename));

        $randomID = uniqid();

        return "{$originalFilenameSlugged}-{$randomID}.{$file->guessExtension()}";
    }

    public function deleteUploadsFolder(): void
    {
        if ($this->filesystem->exists($this->uploadsDirectory)) {
            $this->filesystem->remove($this->uploadsDirectory);
            $this->filesystem->mkdir($this->uploadsDirectory);
        }
    }
}