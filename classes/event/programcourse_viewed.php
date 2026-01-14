<?php

/**
 * The mod_programcourse course module viewed event.
 *
 * @package mod_programcourse
 */

namespace mod_programcourse\event;

defined('MOODLE_INTERNAL') || die();

class programcourse_viewed extends \core\event\course_module_viewed {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->data['objecttable'] = 'programcourse';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
}
