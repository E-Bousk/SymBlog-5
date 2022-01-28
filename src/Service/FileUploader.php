<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private SluggerInterface $slugger;
    private string $uploadsDirectory;

    public function __construct(SluggerInterface $slugger, string $uploadsDirectory)
    {
        $this->slugger = $slugger;
        $this->uploadsDirectory = $uploadsDirectory;
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
            $file->move($this->uploadsDirectory, $filename);
        } catch (FileException $fileException) {
            throw $fileException;
        }

        return [
            'fileName' => $filename,
            'filePath' => $this->uploadsDirectory . $filename
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
}