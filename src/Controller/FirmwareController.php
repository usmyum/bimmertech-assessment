<?php

namespace App\Controller;

use App\Service\FirmwareMatcherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FirmwareController extends AbstractController
{
    /**
     * Customer-facing download page.
     */
    #[Route('/', name: 'firmware_download')]
    public function index(): Response
    {
        return $this->render('firmware/index.html.twig');
    }

    #[Route('/api/carplay/software/version', name: 'firmware_api', methods: ['POST'])]
    public function check(Request $request, FirmwareMatcherService $matcher): JsonResponse
    {
        $version = trim((string)$request->request->get('version', ''));
        $hwVersion = trim((string)$request->request->get('hwVersion', ''));

        if (empty($version)) {
            return new JsonResponse(['msg' => 'Version is required']);
        }

        if (empty($hwVersion)) {
            return new JsonResponse(['msg' => 'HW Version is required']);
        }

        return new JsonResponse($matcher->match($version, $hwVersion));
    }
}
