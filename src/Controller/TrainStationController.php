<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class TrainStationController extends AbstractController
{

    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;  
    }

    #[Route('/train/station/{page}', name: 'app_train_station', defaults: ['page' => 1])]
    public function index(Request $request, int $page): Response
    {
        $projectDir = $this->kernel->getProjectDir();
        $filePath = $projectDir . '/emplacement-des-gares-idf.csv';
        $perPage = 50;
   
        if (($handle = fopen($filePath, 'r')) !== false) {

            $data = $this->csvToKeyValue($filePath);
            $categories = $data[1];
            $data = $data[0];

            $category = $request->query->get('category');
            $search = $request->query->get('search');

            dump($category, $search);
            if (!empty($category) || !empty($search)) {
                $data = $this->search($data, $category, $search);
            }
    
            $paginatedData = $this->paginate($data, $perPage, $page);
            $totalPages = ceil(count($data) / $perPage);

            return $this->render('train_station/index.html.twig', [
                'controller_name' => 'TrainStationController',
                'data' => $paginatedData,
                'categories' => $categories,
                'page' => $page,
                'totalPages' => $totalPages
            ]);
            
        } else {
            throw new \Exception('Erreur lors de l\'ouverture du fichier CSV');
        }
    }

    function csvToKeyValue($filePath) {
        $data = [];
        $firstLoop = false;
        $categories = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 0, ';');;

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (!$firstLoop) {
                    foreach ($row as $key => $value) {
                        $categories[] = trim($headers[$key]);
                    }
                    $firstLoop = true;
                }
                
                $rowData = [];
                foreach ($row as $key => $value) {
                    $rowData[trim($headers[$key])] = $value;
                }
                $data[] = $rowData;
                
            }
            fclose($handle);
        }
        return [$data, $headers];
    }

    function paginate($data, $perPage, $page)
    {
        if ($page < 1 || $perPage < 1) {
            return [];
        }

        $startIndex = ($page - 1) * $perPage;
        $endIndex = $startIndex + $perPage;

        return array_slice($data, $startIndex, $endIndex - $startIndex);
    }

    function getCategories($headers) {
        $categories = [];
        foreach ($headers as $key => $value) {
            $categories[] = $key;
        }
        return $categories;
    }

    function search($tab, $key, $value) {
        $results = [];
        foreach ($tab as $element) {
            if (is_array($element) && isset($element[$key]) && strpos($element[$key], $value) !== false) {
                $results[] = $element;
            }
        }
        return $results;
    }
}