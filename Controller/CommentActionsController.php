<?php
namespace Kanboard\Plugin\CommentActions\Controller;


use Kanboard\Controller\CommentController;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Model\ConfigModel;
use Kanboard\Model\UserModel;

class CommentActionsController extends CommentController
{

    /**
     * Add comment form
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws AccessForbiddenException
     * @throws PageNotFoundException
     */
    public function create(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $task = $this->getTask();
        $values['project_id'] = $task['project_id'];

        $this->response->html($this->helper->layout->task('comment/create', array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'project' => $project,
            'comment_actions_enabled' => $this->isCommentActionsEnabled(),
            'users_list' => $this->getAllUsers($values['project_id'])
        )));
    }

    /**
     * Add a comment
     *
     * @access public
     */
    public function save()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();
        $values['task_id'] = $task['id'];
        $values['user_id'] = $this->userSession->getId();
        $values['project_id'] = $task['project_id'];
        $actionPluginEnabled = $this->isCommentActionsEnabled();

        $actionsEnabled = isset($values['assign_issue']) && $values['assign_issue'];
        if( $actionPluginEnabled && $actionsEnabled ) {
            //TaskModificationController
            $task['assignee_username'] = 'admin';
//            var_dump($values);
//            var_dump($task);
//            die;
        }

        list($valid, $errors) = $this->commentValidator->validateCreation($values);
           var_dump($valid, $errors);
            die;
        if ($valid) {
            if ($this->commentModel->create($values) !== false) {
                $this->flash->success(t('Comment added successfully.'));
            } else {
                $this->flash->failure(t('Unable to create your comment.'));
            }

            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'], 'project_id' => $task['project_id']), 'comments'), true);
        } else {
            $this->create($values, $errors);
        }
    }

    /**
     * Edit a comment
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws AccessForbiddenException
     * @throws PageNotFoundException
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();
        $comment = $this->getComment($task);

        if (empty($values)) {
            $values = $comment;
        }

        $values['project_id'] = $task['project_id'];

        $this->response->html($this->template->render('comment/edit', array(
            'values' => $values,
            'errors' => $errors,
            'comment' => $comment,
            'task' => $task,
            'comment_actions_enabled' => $this->isCommentActionsEnabled(),
            'users_list' => $this->getAllUsers($values['project_id'])
        )));
    }

    /**
     * Update and validate a comment
     *
     * @access public
     */
    public function update()
    {
        $task = $this->getTask();
        $comment = $this->getComment($task);

        $values = $this->request->getValues();
        $values['id'] = $comment['id'];
        $values['task_id'] = $task['id'];
        $values['user_id'] = $comment['user_id'];
        $values['project_id'] = $task['project_id'];
        $actionPluginEnabled = $this->isCommentActionsEnabled();

        $actionsEnabled = isset($values['assign_issue']) && $values['assign_issue'];
        if( $actionPluginEnabled && $actionsEnabled ) {
//            zuweisung an
        }
        list($valid, $errors) = $this->commentValidator->validateModification($values);

        if ($valid) {
            if ($this->commentModel->update($values)) {
                $this->flash->success(t('Comment updated successfully.'));
            } else {
                $this->flash->failure(t('Unable to update your comment.'));
            }

            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'], 'project_id' => $task['project_id'])), true);
            return;
        }

        $this->edit($values, $errors);
    }


    protected function isCommentActionsEnabled() {
        return $this->configModel->getOption('comment_actions');
    }


    protected function getAllUsers($project_id) {
        $array = $this->projectUserRoleModel->getAssignableUsersList($project_id);
        $found_tag['name'] = 'Unassigned';
        foreach ($array as $key => $tag_name) {
            if($tag_name == $found_tag['name']) {
                unset($array[$key]);
            }
        }
        return $this->userModel->prepareList($array);
    }
}
