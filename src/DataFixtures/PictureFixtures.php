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
    private static array $pictures = [
        '553-800x800.jpg',
        '807-1775x1006.jpg',
        '977-536x354.jpg',
        'placeimg_400_400_nature1.jpg'
    ];

    private string $filesToUploadDirectory;
    private FileUploader $fileUploader;
    
    public function __construct(FileUploader $fileUploader, KernelInterface $kernel)
    {
        $this->fileUploader = $fileUploader;
        // Dossier dans lequel sont les images à 'uploader'
        $this->filesToUploadDirectory = "{$kernel->getProjectDir()}/public/to-upload/";
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
        foreach (self::$pictures as $key => $pictureFile) {
            $picture = new Picture();

            [
                'fileName' => $pictureName,
                'filePath' => $picturePath
            ] = $this->fileUploader->upload(
                    new UploadedFile(
                        $this->filesToUploadDirectory . $pictureFile,
                        $pictureFile,
                        null,
                        null,
                        true
                    )
                )
            ;

            $picture->setPictureName($pictureName)
                ->setPicturePath($picturePath)
            ;

            $this->addReference("picture{$key}", $picture);

            $this->manager->persist($picture);

            // Efface le dossier « /public/to-upload » après avoir traité tous les fichiers
            if ($key === array_key_last(self::$pictures)) {
                rmdir($this->filesToUploadDirectory);
            }
        }
    }
}
