<?php

namespace App\Controller;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class DatabaseBackupController extends AbstractController
{
    /**
     *  @Route("/database-backup", name="database_backup", methods={"GET", "POST"}, defaults={"_public_access": false, "_role_required": "ROLE_ADMIN"})
     * 
     * @param string $projectDirectory 
     * @param KernelInterface $kernel 
     * @return Response 
     */
    public function index(string $projectDirectory, KernelInterface $kernel, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            
            if ($user === null) {
                throw new \LogicException('User cannot be null...');
            }
            
            $application = new Application($kernel);
            
            $application->setAutoExit(false);
            
            $input = new ArrayInput([
                'command' => 'app:backup-db'
            ]);
            
            $output = new NullOutput();
            
            try {
                $application->run($input, $output);
            } catch (Exception $error) {
                throw new Exception($error);
            }
            
            $backupFile = "{$projectDirectory}/var/BDD/Backup/backup.sql";
            
            $currentDatetimeString = (new \DateTimeImmutable())->format('d-m-Y-H-i-s');
            
            $backupFileRenamed = "{$projectDirectory}/var/BDD/Backup/backup.sql-{$currentDatetimeString}.sql";
            
            $fileSystem = new Filesystem();
            
            try {
                $fileSystem->rename($backupFile, $backupFileRenamed);
            } catch (IOException $error) {
                throw new IOException($error);
            }
            
            if (file_exists($backupFileRenamed) === true) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header(sprintf('Content-Disposition: attachment; filename="%s"', basename($backupFileRenamed)));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header(sprintf('Content-Length: %s', filesize($backupFileRenamed)));
                
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                readfile($backupFileRenamed);
                
                exit();
            }
        }
        
        // return new Response();        
        return $this->render('bdd-backup/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
