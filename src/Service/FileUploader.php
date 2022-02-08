<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FileUploader
{
    // NOTE: « $uploadsDirectory » est définit dans « services.yaml »
    private string $uploadsDirectory;
    private SluggerInterface $slugger;
    private Filesystem $filesystem;

    public function __construct(string $uploadsDirectory, SluggerInterface $slugger, Filesystem $filesystem)
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
        } catch (IOExceptionInterface $exception) {
            echo "Une erreur est survenue lors de la copie de fichier(s) dans ".$exception->getPath();
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

    public function emptyUploadsFolder(): void
    {
        if ($this->filesystem->exists($this->uploadsDirectory)) {
            $this->filesystem->remove((new Finder())->files()->in($this->uploadsDirectory));
        }
    }
}