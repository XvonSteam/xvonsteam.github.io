<?php

namespace xvonsteam;

class functionDB
{
    private $action;
    private $fullname;
    private $username;
    private $email;
    private $password;
    private $message;
    private $targetUser;
    private $dataDir;
    private $usersFile;
    private $messageDir;

    function __construct($action, $username = null, $email = null, $password = null, $message = null, $targetUser = null, $fullname = null)
    {
        $this->setAction($action);
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->message = $message;
        $this->targetUser = $targetUser;
        $this->fullname = $fullname;

        $this->dataDir = __DIR__ . '/database';
        $this->usersFile = $this->dataDir . '/users.php';
        $this->messageDir = $this->dataDir . '/message';

        if (!is_dir($this->messageDir)) {
            mkdir($this->messageDir, 0777, true);
        }
        if (!file_exists($this->usersFile)) {
            file_put_contents($this->usersFile, "<?php return [];");
        }
    }

    public function setAction($action)
    {
        $validActions = ['login', 'register', 'create', 'update', 'view', 'delete', 'GET', 'POST', 'sendMessage', 'getMessages', 'getRegisteredUsers', 'getAllUsersDetails'];
        if (in_array($action, $validActions)) {
            $this->action = $action;
        } else {
            throw new \InvalidArgumentException("Invalid action: $action");
        }
    }

    public function performAction()
    {
        switch ($this->action) {
            case 'login':
                return $this->login();
            case 'register':
                return $this->register();
            case 'create':
                return $this->create();
            case 'update':
                return $this->update();
            case 'view':
                return $this->view();
            case 'delete':
                return $this->delete();
            case 'GET':
                return $this->get();
            case 'POST':
                return $this->post();
            case 'sendMessage':
                return $this->sendMessage();
            case 'getMessages':
                return $this->getMessages(); // Ini yang akan diubah
            case 'getRegisteredUsers':
                return $this->getRegisteredUsers();
            case 'getAllUsersDetails':
                return $this->getAllUsersDetails();
            default:
                throw new \Exception("Action not defined.");
        }
    }

    public function register()
    {
        if (!$this->fullname || !$this->username || !$this->email || !$this->password) {
            return "Registration failed: full name, username, email, and password are required.";
        }

        $users = @include $this->usersFile;
        if (!is_array($users)) {
            $users = [];
        }

        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $this->username) {
                return "Registration failed: username '{$this->username}' already exists.";
            }
        }

        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

        $userData = [
            'fullname' => $this->fullname,
            'username' => $this->username,
            'email' => $this->email,
            'password' => $hashedPassword
        ];
        $users[] = $userData;

        file_put_contents($this->usersFile, "<?php return " . var_export($users, true) . ";");

        return "User '{$this->username}' registered successfully.";
    }

    public function login()
    {
        if (!$this->username || !$this->password) {
            return "Login failed: username and password are required.";
        }

        $users = @include $this->usersFile;
        if (!is_array($users)) {
            return "Login failed: No registered users found or users file is corrupted.";
        }

        $foundUser = null;
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $this->username) {
                $foundUser = $user;
                break;
            }
        }

        if (!$foundUser) {
            return "Login failed: user '{$this->username}' not found.";
        }

        if (password_verify($this->password, $foundUser['password'])) {
            return "User '{$this->username}' logged in successfully.";
        } else {
            return "Login failed: incorrect password.";
        }
    }

    public function create()
    {
        return "Function not implemented for this model.";
    }
    public function update()
    {
        return "Function not implemented for this model.";
    }
    public function view()
    {
        return "Function not implemented for this model.";
    }
    public function delete()
    {
        return "Function not implemented for this model.";
    }
    public function get()
    {
        return "Function not implemented for this model.";
    }
    public function post()
    {
        return $this->register();
    }

    private function getConversationFileName($user1, $user2)
    {
        $users = [$user1, $user2];
        sort($users);
        return implode('-', $users) . '.php';
    }

    public function sendMessage()
    {
        if (!$this->username || !$this->targetUser || !$this->message) {
            return "Failed to send message: sender, receiver, and message are required.";
        }

        if ($this->username === $this->targetUser) {
            return "Cannot send message to yourself.";
        }

        $users = @include $this->usersFile;
        if (!is_array($users)) {
            return "Failed to send message: Users data corrupted.";
        }
        $targetUserExists = false;
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $this->targetUser) {
                $targetUserExists = true;
                break;
            }
        }
        if (!$targetUserExists) {
            return "Failed to send message: target user '{$this->targetUser}' does not exist.";
        }


        $conversationFile = $this->messageDir . '/' . $this->getConversationFileName($this->username, $this->targetUser);

        if (!file_exists($conversationFile)) {
            file_put_contents($conversationFile, "<?php return [];");
        }

        $messages = @include $conversationFile;
        if (!is_array($messages)) {
            $messages = [];
        }

        $newMessage = [
            'timestamp' => date('Y-m-d H:i:s'),
            'sender' => $this->username,
            'message' => $this->message
        ];
        $messages[] = $newMessage;

        file_put_contents($conversationFile, "<?php return " . var_export($messages, true) . ";");

        return "Message sent successfully to {$this->targetUser}.";
    }

    public function getMessages()
    {
        if (!$this->username || !$this->targetUser) {
            return json_encode(['status' => 'error', 'message' => 'Sender and receiver are required to get messages.']);
        }

        $users = @include $this->usersFile;
        if (!is_array($users)) {
            return json_encode(['status' => 'error', 'message' => 'Users data corrupted.']);
        }
        $targetUserExists = false;
        foreach ($users as $user) {
            if (isset($user['username']) && $user['username'] === $this->targetUser) {
                $targetUserExists = true;
                break;
            }
        }
        if (!$targetUserExists) {
            return json_encode(['status' => 'error', 'message' => "Target user '{$this->targetUser}' does not exist."]);
        }

        $conversationFile = $this->messageDir . '/' . $this->getConversationFileName($this->username, $this->targetUser);

        if (!file_exists($conversationFile)) {
            return json_encode([]);
        }
        $messages = @include $conversationFile;
        if (!is_array($messages)) {
            $messages = [];
        }

        // --- Perubahan di sini: Hapus JSON_PRETTY_PRINT ---
        return json_encode($messages);
    }

    public function getRegisteredUsers()
    {
        $users_details = [];
        $allUsersData = @include $this->usersFile;

        if (is_array($allUsersData)) {
            foreach ($allUsersData as $user) {
                if (isset($user['username'])) {
                    $current_user_in_loop = $user['username'];

                    if ($this->username === null || $current_user_in_loop !== $this->username) {
                        $has_messages = false;
                        $conversationFile = $this->messageDir . '/' . $this->getConversationFileName($this->username, $current_user_in_loop);
                        if (file_exists($conversationFile)) {
                            $messages_content = @include $conversationFile;
                            if (is_array($messages_content) && !empty($messages_content)) {
                                $has_messages = true;
                            }
                        }

                        $users_details[] = [
                            'username' => $current_user_in_loop,
                            'fullname' => $user['fullname'] ?? $current_user_in_loop,
                            'email' => $user['email'] ?? '-',
                            'has_messages' => $has_messages
                        ];
                    }
                }
            }
        }
        usort($users_details, function ($a, $b) {
            return strcmp($a['username'], $b['username']);
        });
        return json_encode($users_details, JSON_PRETTY_PRINT);
    }

    public function getAllUsersDetails()
    {
        $allUsersData = @include $this->usersFile;
        if (is_array($allUsersData)) {
            return $allUsersData;
        }
        return [];
    }

    public function export()
    {
        return var_export($this, true);
    }
}
