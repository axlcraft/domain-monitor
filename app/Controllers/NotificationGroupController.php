<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\NotificationGroup;
use App\Models\NotificationChannel;

class NotificationGroupController extends Controller
{
    private NotificationGroup $groupModel;
    private NotificationChannel $channelModel;

    public function __construct()
    {
        $this->groupModel = new NotificationGroup();
        $this->channelModel = new NotificationChannel();
    }

    public function index()
    {
        $groups = $this->groupModel->getAllWithChannelCount();

        $this->view('groups/index', [
            'groups' => $groups,
            'title' => 'Notification Groups'
        ]);
    }

    public function create()
    {
        $this->view('groups/create', [
            'title' => 'Create Notification Group'
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/groups/create');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/groups/create');

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $_SESSION['error'] = 'Group name is required';
            $this->redirect('/groups/create');
            return;
        }

        // Validate length
        $nameError = \App\Helpers\InputValidator::validateLength($name, 255, 'Group name');
        if ($nameError) {
            $_SESSION['error'] = $nameError;
            $this->redirect('/groups/create');
            return;
        }

        $descError = \App\Helpers\InputValidator::validateLength($description, 1000, 'Description');
        if ($descError) {
            $_SESSION['error'] = $descError;
            $this->redirect('/groups/create');
            return;
        }

        $id = $this->groupModel->create([
            'name' => $name,
            'description' => $description
        ]);

        $_SESSION['success'] = "Group '$name' created successfully";
        $this->redirect("/groups/edit?id=$id");
    }

    public function edit()
    {
        $id = $_GET['id'] ?? 0;
        $group = $this->groupModel->getWithDetails($id);

        if (!$group) {
            $_SESSION['error'] = 'Group not found';
            $this->redirect('/groups');
            return;
        }

        $this->view('groups/edit', [
            'group' => $group,
            'title' => 'Edit Group: ' . $group['name']
        ]);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/groups');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/groups');

        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $_SESSION['error'] = 'Group name is required';
            $this->redirect("/groups/edit?id=$id");
            return;
        }

        // Validate length
        $nameError = \App\Helpers\InputValidator::validateLength($name, 255, 'Group name');
        if ($nameError) {
            $_SESSION['error'] = $nameError;
            $this->redirect("/groups/edit?id=$id");
            return;
        }

        $descError = \App\Helpers\InputValidator::validateLength($description, 1000, 'Description');
        if ($descError) {
            $_SESSION['error'] = $descError;
            $this->redirect("/groups/edit?id=$id");
            return;
        }

        $this->groupModel->update($id, [
            'name' => $name,
            'description' => $description
        ]);

        $_SESSION['success'] = 'Group updated successfully';
        $this->redirect("/groups/edit?id=$id");
    }

    public function delete()
    {
        $id = $_GET['id'] ?? 0;
        $group = $this->groupModel->find($id);

        if (!$group) {
            $_SESSION['error'] = 'Group not found';
            $this->redirect('/groups');
            return;
        }

        $this->groupModel->deleteWithRelations($id);
        $_SESSION['success'] = 'Group deleted successfully';
        $this->redirect('/groups');
    }

    public function addChannel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/groups');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/groups');

        $groupId = (int)$_POST['group_id'];
        $channelType = $_POST['channel_type'] ?? '';

        $config = $this->buildChannelConfig($channelType, $_POST);

        if (!$config) {
            $missingField = '';
            switch ($channelType) {
                case 'email':
                    $missingField = 'email address';
                    break;
                case 'telegram':
                    $missingField = empty($_POST['bot_token']) ? 'bot token' : 'chat ID';
                    break;
                case 'discord':
                case 'slack':
                    $missingField = 'webhook URL';
                    break;
            }
            
            $_SESSION['error'] = "Invalid channel configuration: Missing {$missingField}";
            $this->redirect("/groups/edit?id=$groupId");
            return;
        }

        $this->channelModel->createChannel($groupId, $channelType, $config);

        $_SESSION['success'] = 'Channel added successfully';
        $this->redirect("/groups/edit?id=$groupId");
    }

    public function deleteChannel()
    {
        $id = $_GET['id'] ?? 0;
        $groupId = $_GET['group_id'] ?? 0;

        $this->channelModel->delete($id);

        $_SESSION['success'] = 'Channel deleted successfully';
        $this->redirect("/groups/edit?id=$groupId");
    }

    public function toggleChannel()
    {
        $id = $_GET['id'] ?? 0;
        $groupId = $_GET['group_id'] ?? 0;

        $this->channelModel->toggleActive($id);

        $_SESSION['success'] = 'Channel status updated';
        $this->redirect("/groups/edit?id=$groupId");
    }

    private function buildChannelConfig(string $type, array $data): ?array
    {
        switch ($type) {
            case 'email':
                if (empty($data['email'])) return null;
                return ['email' => $data['email']];

            case 'telegram':
                if (empty($data['bot_token']) || empty($data['chat_id'])) return null;
                return [
                    'bot_token' => $data['bot_token'],
                    'chat_id' => $data['chat_id']
                ];

            case 'discord':
                $webhookUrl = $data['discord_webhook_url'] ?? '';
                if (empty($webhookUrl)) return null;
                return ['webhook_url' => $webhookUrl];

            case 'slack':
                $webhookUrl = $data['slack_webhook_url'] ?? '';
                if (empty($webhookUrl)) return null;
                return ['webhook_url' => $webhookUrl];

            default:
                return null;
        }
    }

    /**
     * Bulk delete notification groups
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/groups');
            return;
        }

        $this->verifyCsrf('/groups');

        $groupIdsJson = $_POST['group_ids'] ?? '[]';
        $groupIds = json_decode($groupIdsJson, true);

        if (empty($groupIds) || !is_array($groupIds)) {
            $_SESSION['error'] = 'No groups selected for deletion';
            $this->redirect('/groups');
            return;
        }

        $deletedCount = 0;

        foreach ($groupIds as $groupId) {
            try {
                $this->groupModel->deleteWithRelations((int)$groupId);
                $deletedCount++;
            } catch (\Exception $e) {
                // Continue with next group
            }
        }

        $_SESSION['success'] = "Successfully deleted $deletedCount notification group(s)";
        $this->redirect('/groups');
    }
}

