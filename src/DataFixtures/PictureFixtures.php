<?php

namespace App\DataFixtures;

use App\Entity\Picture;
use App\Service\FileUploader;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Finder\Finder;

class PictureFixtures extends Fixture
{
    private FileUploader $fileUploader;
    private string $toUploadDirectory;
    
    public function __construct(FileUploader $fileUploader, KernelInterface $kernel)
    {
        $this->fileUploader = $fileUploader;
        $this->toUploadDirectory = "{$kernel->getProjectDir()}/public/to-upload/";
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
        $this->fileUploader->emptyUploadsFolder();
        
        $finder = new Finder();
        $finder->files()->in($this->toUploadDirectory);
        $key = -1;

        foreach ($finder as $file) {
            $picture = new Picture();
            $key += 1;

            [
                'fileName' => $pictureName,
                'filePath' => $picturePath
            ] = $this->fileUploader->upload(
                new UploadedFile(
                    $this->toUploadDirectory . $file->getRelativePathname(),
                    $file->getRelativePathname(),
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
        }
    }
}
