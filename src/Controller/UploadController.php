<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends AbstractController
{
    #[Route('/upload/image', name: 'upload_image', methods: ['POST'])]  
    public function uploadImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        try {
            $file = $request->files->get('image');

            if (!$file) {
                return $this->json(['success' => false, 'error' => 'No file uploaded'], 400);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            $mimeType = $file->getMimeType();
            
            if (!in_array($mimeType, $allowedMimes)) {
                return $this->json([
                    'success' => false, 
                    'error' => 'Invalid file type. Only images allowed.'
                ], 400);
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->json(['success' => false, 'error' => 'File size must be less than 5MB'], 400);
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            $file->move($uploadDir, $newFilename);

            return $this->json([
                'success' => true,
                'filename' => $newFilename,
                'url' => '/images/' . $newFilename
            ]);

        } catch (FileException $e) {
            return $this->json([
                'success' => false, 
                'error' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'error' => 'Unexpected error: ' . $e->getMessage()
            ], 500);
        }
    }
}