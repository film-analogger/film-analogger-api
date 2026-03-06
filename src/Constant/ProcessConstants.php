<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

final class ProcessConstants
{
    const CHEMISTRY_C41 = 'C-41';
    const CHEMISTRY_E6 = 'E-6';
    const CHEMISTRY_BW = 'B&W';
    const CHEMISTRY_ECN2 = 'ECN-2';
    const CHEMISTRY_RA4 = 'RA4';

    const CHEMISTRY_PROCESSES = [
        self::CHEMISTRY_C41,
        self::CHEMISTRY_E6,
        self::CHEMISTRY_BW,
        self::CHEMISTRY_ECN2,
        self::CHEMISTRY_RA4,
    ];

    const CHEMISTRY_BW_PAPER_DEVELOPER = 'BW_PAPER_DEVELOPER';
    const CHEMISTRY_BW_FILM_DEVELOPER = 'BW_FILM_DEVELOPER';
    const CHEMISTRY_C41_COLOR_DEVELOPER = 'C41_COLOR_DEVELOPER';
    const CHEMISTRY_RA4_COLOR_DEVELOPER = 'RA4_COLOR_DEVELOPER';
    const CHEMISTRY_E6_COLOR_DEVELOPER = 'E6_COLOR_DEVELOPER';
    const CHEMISTRY_E6_FILM_DEVELOPER = 'E6_FILM_DEVELOPER';
    const CHEMISTRY_FIXER = 'FIXER';
    const CHEMISTRY_STOP = 'STOP';
    const CHEMISTRY_WETTING_AGENT = 'WETTING_AGENT';
    const CHEMISTRY_FILM_CLEANER = 'FILM_CLEANER';
    const CHEMISTRY_THIOSULFATE_CLEANER = 'THIOSULFATE_CLEANER';
    const CHEMISTRY_TONER = 'TONER';
    const CHEMISTRY_REMJET_REMOVER = 'REMJET_REMOVER';
    const CHEMISTRY_BLEACH = 'BLEACH';
    const CHEMISTRY_STABILIZER = 'STABILIZER';
    const CHEMISTRY_OTHER = 'OTHER';

    const CHEMISTRY_TYPES_C41_FILM = [
        self::CHEMISTRY_C41_COLOR_DEVELOPER,
        self::CHEMISTRY_BLEACH,
        self::CHEMISTRY_STABILIZER,
        self::CHEMISTRY_WETTING_AGENT,
        self::CHEMISTRY_REMJET_REMOVER,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES_ECN2_FILM = [
        self::CHEMISTRY_C41_COLOR_DEVELOPER,
        self::CHEMISTRY_BLEACH,
        self::CHEMISTRY_STABILIZER,
        self::CHEMISTRY_WETTING_AGENT,
        self::CHEMISTRY_REMJET_REMOVER,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES_E6_FILM = [
        self::CHEMISTRY_E6_FILM_DEVELOPER,
        self::CHEMISTRY_E6_COLOR_DEVELOPER,
        self::CHEMISTRY_BLEACH,
        self::CHEMISTRY_STABILIZER,
        self::CHEMISTRY_WETTING_AGENT,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES_BW_FILM = [
        self::CHEMISTRY_BW_FILM_DEVELOPER,
        self::CHEMISTRY_FIXER,
        self::CHEMISTRY_STOP,
        self::CHEMISTRY_WETTING_AGENT,
        self::CHEMISTRY_FILM_CLEANER,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES_BW_PAPER = [
        self::CHEMISTRY_BW_PAPER_DEVELOPER,
        self::CHEMISTRY_FIXER,
        self::CHEMISTRY_STOP,
        self::CHEMISTRY_THIOSULFATE_CLEANER,
        self::CHEMISTRY_TONER,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES_RA4_PAPER = [
        self::CHEMISTRY_RA4_COLOR_DEVELOPER,
        self::CHEMISTRY_BLEACH,
        self::CHEMISTRY_FIXER,
        self::CHEMISTRY_STABILIZER,
        self::CHEMISTRY_OTHER,
    ];

    const CHEMISTRY_TYPES = [
        self::CHEMISTRY_BW_PAPER_DEVELOPER,
        self::CHEMISTRY_BW_FILM_DEVELOPER,
        self::CHEMISTRY_C41_COLOR_DEVELOPER,
        self::CHEMISTRY_RA4_COLOR_DEVELOPER,
        self::CHEMISTRY_E6_COLOR_DEVELOPER,
        self::CHEMISTRY_E6_FILM_DEVELOPER,
        self::CHEMISTRY_FIXER,
        self::CHEMISTRY_STOP,
        self::CHEMISTRY_WETTING_AGENT,
        self::CHEMISTRY_FILM_CLEANER,
        self::CHEMISTRY_THIOSULFATE_CLEANER,
        self::CHEMISTRY_TONER,
        self::CHEMISTRY_REMJET_REMOVER,
        self::CHEMISTRY_BLEACH,
        self::CHEMISTRY_STABILIZER,
        self::CHEMISTRY_OTHER,
    ];

    public static function getValidChemistryTypesForProcess(string $process): array
    {
        switch ($process) {
            case self::CHEMISTRY_C41:
                return self::CHEMISTRY_TYPES_C41_FILM;

            case self::CHEMISTRY_ECN2:
                return self::CHEMISTRY_TYPES_ECN2_FILM;

            case self::CHEMISTRY_E6:
                return self::CHEMISTRY_TYPES_E6_FILM;

            case self::CHEMISTRY_BW:
                return self::CHEMISTRY_TYPES_BW_FILM;

            case self::CHEMISTRY_RA4:
                return self::CHEMISTRY_TYPES_RA4_PAPER;

            default:
                return [];
        }
    }

    const FILM_EMULSION_TYPES_COLOR = ['chromogene'];
    const FILM_EMULSION_TYPES_BNW = ['panchromatic', 'orthochromatic', 'chromogene'];

    const FILM_EMULSION_TYPES = ['panchromatic', 'orthochromatic', 'chromogene'];

    public static function getValidEmulsionTypesForProcess(string $process): array
    {
        switch ($process) {
            case self::CHEMISTRY_C41:
            case self::CHEMISTRY_E6:
            case self::CHEMISTRY_ECN2:
                return self::FILM_EMULSION_TYPES_COLOR;

            case self::CHEMISTRY_BW:
                return self::FILM_EMULSION_TYPES_BNW;

            default:
                return self::FILM_EMULSION_TYPES;
        }
    }
}
