<?php

namespace App\Controller;

use App\Repository\SoftwareVersionRepository;
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

    /**
     * API endpoint — POST /api/carplay/software/version
     * Identical behaviour to the original ConnectedSiteController::softwareDownload().
     */
    #[Route('/api/carplay/software/version', name: 'firmware_api', methods: ['POST'])]
    public function check(Request $request, SoftwareVersionRepository $repo): JsonResponse
    {
        $version = (string)$request->request->get('version', '');
        $hwVersion = (string)$request->request->get('hwVersion', '');
        // mcuVersion accepted but not used in matching logic (same as original)

        if (empty($version)) {
            return new JsonResponse(['msg' => 'Version is required']);
        }

        if (empty($hwVersion)) {
            return new JsonResponse(['msg' => 'HW Version is required']);
        }

        // ── Hardware version patterns (copied verbatim from original) ──────────
        $patternST = '/^CPAA_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';
        $patternGD = '/^CPAA_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';
        $patternLCI_CIC = '/^B_C_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
        $patternLCI_NBT = '/^B_N_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
        $patternLCI_EVO = '/^B_E_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

        $hwVersionBool = false;
        $stBool = false;
        $gdBool = false;
        $isLCI = false;
        $lciHwType = '';

        if (preg_match($patternST, $hwVersion)) {
            $hwVersionBool = true;
            $stBool = true;
        }

        if (preg_match($patternGD, $hwVersion)) {
            $hwVersionBool = true;
            $gdBool = true;
        }

        if (preg_match($patternLCI_CIC, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'CIC';
            $stBool = true;
        } elseif (preg_match($patternLCI_NBT, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'NBT';
            $gdBool = true;
        } elseif (preg_match($patternLCI_EVO, $hwVersion)) {
            $hwVersionBool = true;
            $isLCI = true;
            $lciHwType = 'EVO';
            $gdBool = true;
        }

        if (!$hwVersionBool) {
            return new JsonResponse([
                'msg' => 'There was a problem identifying your software. Contact us for help.',
            ]);
        }

        // Strip leading v/V (same as original)
        if (str_starts_with(strtolower($version), 'v')) {
            $version = substr($version, 1);
        }

        // ── Look up version in DB ──────────────────────────────────────────────
        $rows = $repo->findByVersionAlt($version);

        foreach ($rows as $row) {
            $isLCIEntry = str_starts_with(strtolower($row->getName()), 'lci');

            // Standard HW must only match standard entries, LCI must only match LCI
            if ($isLCI !== $isLCIEntry) {
                continue;
            }

            // For LCI, also check hardware type (CIC / NBT / EVO) matches entry name
            if ($isLCI && stripos($row->getName(), $lciHwType) === false) {
                continue;
            }

            if ($row->isLatest()) {
                return new JsonResponse([
                    'versionExist' => true,
                    'msg' => 'Your system is upto date!',
                    'link' => '',
                    'st' => '',
                    'gd' => '',
                ]);
            }

            $latestMsg = $isLCI ? 'v3.4.4' : 'v3.3.7';

            return new JsonResponse([
                'versionExist' => true,
                'msg' => 'The latest version of software is ' . $latestMsg . ' ',
                'link' => $row->getLink() ?? '',
                'st' => $stBool ? ($row->getSt() ?? '') : '',
                'gd' => $gdBool ? ($row->getGd() ?? '') : '',
            ]);
        }

        // No match found
        return new JsonResponse([
            'versionExist' => false,
            'msg' => 'There was a problem identifying your software. Contact us for help.',
            'link' => '',
            'st' => '',
            'gd' => '',
        ]);
    }
}
