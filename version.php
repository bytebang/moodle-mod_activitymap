<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Activitymap module version information
 *
 * @package    mod_activitymap
 * @copyright  2020 Guenther Hutter, Andreas Poetscher
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2020080140;
$plugin->requires  = 2014051200;
$plugin->component = 'mod_activitymap';
$plugin->supported = [37, 39]; // Moodle 3.7.x, 3.8.x and 3.9.x are supported.
$plugin->maturity  = MATURITY_RC;
$plugin->release   = 'v0.98';
