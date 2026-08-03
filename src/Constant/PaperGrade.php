<?php

namespace FilmAnalogger\FilmAnaloggerApi\Constant;

/**
 * Ilford Multigrade contact grade scale, including half-grades and the
 * "no filter" value (white light exposure, nominally ~G2 but dependent on
 * the enlarger lamp's spectrum and drift over its lifetime — not the same
 * as "no information").
 */
enum PaperGrade: string
{
    case NO_FILTER = 'no_filter';
    case G00 = '00';
    case G0 = '0';
    case G0_5 = '0.5';
    case G1 = '1';
    case G1_5 = '1.5';
    case G2 = '2';
    case G2_5 = '2.5';
    case G3 = '3';
    case G3_5 = '3.5';
    case G4 = '4';
    case G4_5 = '4.5';
    case G5 = '5';
}
