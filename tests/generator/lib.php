<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator pour le module programcourse.
 *
 * @package    mod_programcourse
 */
class mod_programcourse_generator extends testing_module_generator
{
    /**
     * Crée une instance de programcourse pour les tests.
     *
     * @param array|null $record Données spécifiques pour l'instance
     * @param array|null $options Options supplémentaires
     * @return stdClass L'objet représentant l'instance créée
     */
    public function create_instance($record = null, array $options = null)
    {
        global $DB;

        $record = (object) (array) $record;

        if (empty($record->course)) {
            throw new coding_exception('Le champ "course" est requis pour créer une instance de programcourse.');
        }

        if (empty($record->courseid)) {
            throw new coding_exception('Le champ "courseid" est requis pour créer une instance de programcourse.');
        }

        if (empty($record->name)) {
            $record->name = 'Activité programcourse de test';
        }

        if (empty($record->timemodified)) {
            $record->timemodified = time();
        }

        if (empty($record->introformat)) {
            $record->introformat = 1;
        }

        if (empty($record->completionall)) {
            $record->completionall = 1;
        }

        // Appelle le parent pour gérer course_modules, course_sections, etc.
        return parent::create_instance($record, $options);
    }
}
