<?php

namespace app\controllers;

use app\controllers\admin\ReportController;
use app\controllers\admin\GradeableController;
use app\controllers\admin\GradeablesController;
use app\models\GradeableComponentMark;
use app\controllers\admin\AdminGradeableController;
use app\controllers\admin\ConfigurationController;
use app\controllers\admin\UsersController;
use app\controllers\admin\LateController;
use app\controllers\admin\PlagiarismController;
use app\libraries\Core;
use app\libraries\Output;
use app\models\User;

/**
 * Class ElectronicRubricController
 * @package app\controllers
 *
 * Processes requests to access and modify electronic
 *  gradeable rubrics
 *
 * TODO: remove all of the duplicate functionality from ElectronicGraderController
 *
 * TODO: should there be aggregate UPDATE requests to support things like reordering?
 *
 * All actions required POST verb (TODO, probably should be other verbs)
 *
 * All actions may return a 401 UNAUTHORIZED if the user is not authorized
 *  to perform the specified action.
 * All actions may return a 400 BAD REQUEST if $_POST format is incorrect
 * All status codes in responses will be the only the number and not the label
 */
class ElectronicRubricController extends AbstractController {

    private static function badRequest() {
        http_response_code(400);
    }

    private static function unauthorized() {
        http_response_code(401);
    }

    private static function forbidden() {
        http_response_code(403);
    }

    private static function notFound() {
        http_response_code(404);
    }



    public function run() {
        if (!$this->assertGrader()) {
            return $this->unauthorized();
        }

        $controller = null;
        switch ($_REQUEST['action']) {
            case 'create_mark':
                $this->createMark();
                break;
            case 'read_marks':
                $this->readMarks();
                break;
            case 'update_mark':
                $this->updateMark();
                break;
            case 'delete_mark':
                $this->deleteMark();
                break;
            case 'reorder_marks':
                $this->reorderMarks();
                break;

            case 'create_component':
                $this->createComponent();
                break;
            case 'read_components':
                $this->readComponents();
                break;
            case 'update_component':
                $this->updateComponent();
                break;
            case 'delete_component':
                $this->deleteComponent();
                break;
            case 'reorder_components':
                $this->reorderComponents();
                break;
            default:
                $this->core->getOutput()->showError("Invalid page request for controller ".get_class($this));
                break;
        }
        $controller->run();
    }

    /**
     * Ensures the user has admin permissions
     */
    private function assertAdmin() {
        return $this->core->getUser()->accessAdmin();
    }

    /**
     * Ensures the user has grader permissions
     */
    private function assertGrader() {
        return $this->core->getUser()->accessGrading();
    }

    /**
     * Creates a new mark in the database
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *  'note'          => The mark's note
     *  'points'        => The mark's point value
     *  'publish'       => If the mark should be published
     *
     * Response format (json): 201 CREATED, 409 CONFLICT
     *  'id'            => mark's id
     */
    private function createMark() {
        $gradeable_id = $_POST['gradeable_id'];
        $gradeable = $this->core->getQueries()->getGradeable($gradeable_id);
        $note = $_POST['note'];
        $points = $_POST['points'];
        foreach ($gradeable->getComponents() as $component) {
            if(is_array($component)) {
                if($component[0]->getId() != $_POST['gradeable_component_id']) {
                    continue;
                }
            } else if ($component->getId() != $_POST['gradeable_component_id']) {
                continue;
            }
            $order_counter = $this->core->getQueries()->getGreatestGradeableComponentMarkOrder($component);
            $order_counter++;
            $mark = new GradeableComponentMark($this->core);
            $mark->setGcId($component->getId());
            $mark->setPoints($points);
            $mark->setNote($note);
            $mark->setOrder($order_counter);
            $mark->save();
        }

        // TODO: is this required?
        $response = array('status' => 'success');
        $this->core->getOutput()->renderJson($response);
        return $response;
    }

    /**
     * Gets all marks for a component and/or gradeable
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id (optional)
     *
     * Response format (json): 200 OK, 404 NOT FOUND
     *  'marks': [
     *      foreach mark in component:
     *          {
     *              'id'    => the mark's id
     *              'score' => the marks's score contribution
     *              'note'  => mark's note
     *              'publish'=>if the mark should be published
     *          }
     *  ]
     */
    private function readMarks() {

    }

    /**
     * Updates a mark in the database
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *  'mark_id'       => The mark's id
     *  'note'          => The mark's note
     *  'points'        => The mark's point value
     *
     * Response format (json): ACCEPTED, 404 NOT FOUND
     */
    private function updateMark() {

    }

    /**
     * Deletes a mark from the database if no one
     *  has been assigned this mark yet
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *  'mark_id'       => The mark's id
     *
     * Response format (json): 204 NO CONTENT, 404 NOT FOUND, 403 FORBIDDEN
     */
    private function deleteMark() {

    }

    /**
     * Swaps the order of two marks
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *  'mark0_id'      => The first mark's id
     *  'mark1_id'      => The second mark's id
     *
     * Response format (json): 201 ACCEPTED, 404 NOT FOUND
     */
    private function reorderMarks() {

    }


    /**
     * Creates a new component in the database
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'title'         => The component title
     *  'ta_msg'        => The message to the TA
     *  'student_msg'   => The message to the student
     *  'points'        => The component's point worth
     *  'ec'            => The max extra credit points possible
     *  'penalty'       => The max penalty points possible
     *  'count_up'      => 0 to count down, otherwise count up
     *
     * Response format (json): 201 CREATED, 409 CONFLICT
     *  'id'            => component's id
     */
    private function createComponent() {
        if(!$this->assertAdmin()) {
            return $this->unauthorized();
        }

    }

    /**
     * Gets all components for a gradeable
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *
     * Response format (json): 200 OK, 404 NOT FOUND
     *  'components': [
     *      foreach component in gradeable:
     *          {
     *              'id'        => component's id
     *              'title'     => component's title
     *              'ta_msg'    => message to the TA
     *              'student_msg'=>message to the student
     *              'points'    => component's point worth
     *              'ec'        => max extra credit points possible
     *              'penalty'   => max penalty points possible
     *              'count_up'  => 0 if count down, 1 if count up
     *          }
     *  ]
     */
    private function readComponents() {

    }

    /**
     * Updates a component in the database
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *
     * One or more of the following
     *  'title'         => The component title
     *  'ta_msg'        => The message to the TA
     *  'student_msg'   => The message to the student
     *  'points'        => The component's point worth
     *  'ec'            => The max extra credit points possible
     *  'penalty'       => The max penalty points possible
     *  'count_up'      => 0 to count down, otherwise count up
     *
     * Response format (json): 201 ACCEPTED, 404 NOT FOUND
     *
     */
    private function updateComponent() {
        if(!$this->assertAdmin()) {
            return $this->unauthorized();
        }

    }

    /**
     * Deletes a component from the database if grading
     *  hasn't started yet
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'component_id'  => The component id
     *
     * Response format (json): 204 NO CONTENT, 404 NOT FOUND, 403 FORBIDDEN
     */
    private function deleteComponent() {
        if(!$this->assertAdmin()) {
            return $this->unauthorized();
        }

    }

    /**
     * Swaps the order of two components
     *
     * Expected $_POST format:
     *  'gradeable_id'  => The gradeable id
     *  'comp0_id'      => The first component's id
     *  'comp1_id'      => The second component's id
     *
     * Response format (json): 201 ACCEPTED, 404 NOT FOUND
     */
    private function reorderComponents() {
        if(!$this->assertAdmin()) {
            return $this->unauthorized();
        }

    }
}
