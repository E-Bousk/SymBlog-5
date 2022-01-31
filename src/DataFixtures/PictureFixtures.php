<?php

namespace App\DataFixtures;

use App\Entity\Picture;
use App\Service\FileUploader;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class PictureFixtures extends Fixture
{
    // Tableau avec les fichiers à traiter (dans le dossier « /public/to-upload »)
    /** @var array<string> */
    private array $pictures;
    
    private FileUploader $fileUploader;
    private string $toUploadDirectory;
    
    public function __construct(FileUploader $fileUploader, KernelInterface $kernel)
    {
        $this->fileUploader = $fileUploader;
        // Dossier des images à 'uploader'
        $this->toUploadDirectory = "{$kernel->getProjectDir()}/public/to-upload/";
        // Récupère tous les fichiers du répertoire et créé un tableau avec
        $this->pictures = array_values(array_diff(scandir($this->toUploadDirectory), array('.', '..')));
    }
    
    private ObjectManager $manager;
    
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        
        $this->generateArticlePicture();
        
        $this->manager->flush();
    }

    public function generateArticlePicture(): void
    {
        $this->fileUploader->deleteUploadsFolder();
        
        foreach ($this->pictures as $key => $pictureFile) {
            $picture = new Picture();
            
            [
                'fileName' => $pictureName,
                'filePath' => $picturePath
            ] = $this->fileUploader->upload(
                new UploadedFile(
                    $this->toUploadDirectory . $pictureFile,
                    $pictureFile,
                    null,
                    null,
                    true
                )
            );
            $picture->setPictureName($pictureName)
                ->setPicturePath($picturePath)
            ;

            $this->addReference("picture{$key}", $picture);

            $this->manager->persist($picture);

            // // Efface le dossier « /public/to-upload » après avoir traité tous les fichiers
            // if ($key === array_key_last(self::$pictures)) {
            //     rmdir($this->toUploadDirectory);
            // }
        }
    }
}
