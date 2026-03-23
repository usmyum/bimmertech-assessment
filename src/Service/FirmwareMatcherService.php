<?php

namespace App\Service;

use App\Repository\SoftwareVersionRepository;

class FirmwareMatcherService
{
    // Hardware version patterns
    private const PATTERN_ST = '/^CPAA_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';
    private const PATTERN_GD = '/^CPAA_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}(_[A-Z]+)?$/i';
    private const PATTERN_LCI_CIC = '/^B_C_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
    private const PATTERN_LCI_NBT = '/^B_N_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';
    private const PATTERN_LCI_EVO = '/^B_E_G_[0-9]{4}\.[0-9]{2}\.[0-9]{2}$/i';

    public function __construct(
        private readonly SoftwareVersionRepository $repo
    )
    {
    }

    /**
     * Main entry point. Returns a result array ready to be JSON-encoded.
     */
    public function match(string $version, string $hwVersion): array
    {
        $hw = $this->detectHardware($hwVersion);

        if ($hw === null) {
            return ['msg' => 'There was a problem identifying your software. Contact us for help.'];
        }

        $version = $this->stripLeadingV($version);
        $rows = $this->repo->findByVersionAlt($version);

        foreach ($rows as $row) {
            $isLCIEntry = str_starts_with(strtolower($row->getName()), 'lci');

            if ($hw['isLCI'] !== $isLCIEntry) {
                continue;
            }

            if ($hw['isLCI'] && stripos($row->getName(), $hw['lciHwType']) === false) {
                continue;
            }

            if ($row->isLatest()) {
                return [
                    'versionExist' => true,
                    'msg' => 'Your system is upto date!',
                    'link' => '',
                    'st' => '',
                    'gd' => '',
                ];
            }

            $latestMsg = $hw['isLCI'] ? 'v3.4.4' : 'v3.3.7';

            return [
                'versionExist' => true,
                'msg' => 'The latest version of software is ' . $latestMsg . ' ',
                'link' => $row->getLink() ?? '',
                'st' => $hw['st'] ? ($row->getSt() ?? '') : '',
                'gd' => $hw['gd'] ? ($row->getGd() ?? '') : '',
            ];
        }

        return [
            'versionExist' => false,
            'msg' => 'There was a problem identifying your software. Contact us for help.',
            'link' => '',
            'st' => '',
            'gd' => '',
        ];
    }

    /**
     * Detects hardware type from the HW version string.
     * Returns an array with hardware flags, or null if unrecognised.
     */
    private function detectHardware(string $hwVersion): ?array
    {
        if (preg_match(self::PATTERN_LCI_CIC, $hwVersion)) {
            return ['isLCI' => true, 'lciHwType' => 'CIC', 'st' => true, 'gd' => false];
        }

        if (preg_match(self::PATTERN_LCI_NBT, $hwVersion)) {
            return ['isLCI' => true, 'lciHwType' => 'NBT', 'st' => false, 'gd' => true];
        }

        if (preg_match(self::PATTERN_LCI_EVO, $hwVersion)) {
            return ['isLCI' => true, 'lciHwType' => 'EVO', 'st' => false, 'gd' => true];
        }

        if (preg_match(self::PATTERN_GD, $hwVersion)) {
            return ['isLCI' => false, 'lciHwType' => '', 'st' => false, 'gd' => true];
        }

        if (preg_match(self::PATTERN_ST, $hwVersion)) {
            return ['isLCI' => false, 'lciHwType' => '', 'st' => true, 'gd' => false];
        }

        return null;
    }

    private function stripLeadingV(string $version): string
    {
        return str_starts_with(strtolower($version), 'v')
            ? substr($version, 1)
            : $version;
    }
}
