<?php
$title = 'Edit Notification Group';
$pageTitle = 'Edit Notification Group';
$pageDescription = htmlspecialchars($group['name']);
$pageIcon = 'fas fa-edit';
ob_start();
?>

<div class="max-w-7xl mx-auto space-y-4">
    <!-- Group Details Form -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-info-circle text-gray-400 mr-2 text-sm"></i>
                Group Details
            </h2>
        </div>
        
        <div class="p-6">
            <form method="POST" action="/groups/update" class="space-y-5">
                <input type="hidden" name="id" value="<?= $group['id'] ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Group Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Group Name *
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                               value="<?= htmlspecialchars($group['name']) ?>"
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Description (Optional)
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                                  rows="3"><?= htmlspecialchars($group['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" 
                            class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors text-sm">
                        <i class="fas fa-save mr-2"></i>
                        Update Group
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Channels -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-plug text-gray-400 mr-2 text-sm"></i>
                Notification Channels
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($group['channels'])): ?>
                <div class="text-center py-10">
                    <i class="fas fa-plug text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">No channels configured yet</p>
                    <p class="text-sm text-gray-400 mt-1">Add your first channel below to start receiving notifications</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <?php foreach ($group['channels'] as $channel): 
                        $config = json_decode($channel['channel_config'], true);
                        $icons = ['email' => 'fa-envelope', 'telegram' => 'fa-telegram', 'discord' => 'fa-discord', 'slack' => 'fa-slack'];
                        $colors = ['email' => 'blue', 'telegram' => 'blue', 'discord' => 'indigo', 'slack' => 'purple'];
                        $icon = $icons[$channel['channel_type']] ?? 'fa-bell';
                        $color = $colors[$channel['channel_type']] ?? 'gray';
                    ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 bg-<?= $color ?>-100 rounded-lg flex items-center justify-center">
                                    <i class="fab <?= $icon ?> text-<?= $color ?>-600 text-xl"></i>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $channel['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' ?>">
                                    <?= $channel['is_active'] ? 'Active' : 'Disabled' ?>
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2"><?= ucfirst($channel['channel_type']) ?></h3>
                            <p class="text-sm text-gray-600 mb-4 truncate">
                                <?php
                                if ($channel['channel_type'] === 'email') {
                                    echo htmlspecialchars($config['email'] ?? 'No email');
                                } elseif ($channel['channel_type'] === 'telegram') {
                                    echo "Chat: " . htmlspecialchars($config['chat_id'] ?? 'N/A');
                                } else {
                                    echo "Webhook configured";
                                }
                                ?>
                            </p>
                            <div class="flex gap-2">
                                <a href="/channels/toggle?id=<?= $channel['id'] ?>&group_id=<?= $group['id'] ?>" 
                                   class="flex-1 px-3 py-2 bg-yellow-50 text-yellow-700 rounded text-center text-sm hover:bg-yellow-100 transition-colors duration-150">
                                    <i class="fas fa-<?= $channel['is_active'] ? 'pause' : 'play' ?> mr-1"></i>
                                    <?= $channel['is_active'] ? 'Disable' : 'Enable' ?>
                                </a>
                                <a href="/channels/delete?id=<?= $channel['id'] ?>&group_id=<?= $group['id'] ?>" 
                                   class="flex-1 px-3 py-2 bg-red-50 text-red-700 rounded text-center text-sm hover:bg-red-100 transition-colors duration-150"
                                   onclick="return confirm('Delete this channel?')">
                                    <i class="fas fa-trash mr-1"></i>
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Add Channel Form -->
            <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-plus-circle text-gray-400 mr-2 text-sm"></i>
                    Add New Channel
                </h3>

                <form method="POST" action="/channels/add" id="channelForm" class="space-y-5">
                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">

                    <!-- Channel Type -->
                    <div>
                        <label for="channel_type" class="block text-sm font-medium text-gray-700 mb-1.5">Channel Type</label>
                        <select id="channel_type" 
                                name="channel_type" 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                                onchange="toggleChannelFields()">
                            <option value="">-- Select Channel Type --</option>
                            <option value="email">Email</option>
                            <option value="telegram">Telegram</option>
                            <option value="discord">Discord</option>
                            <option value="slack">Slack</option>
                        </select>
                    </div>

                    <!-- Email Fields -->
                    <div id="email_fields" class="hidden space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                                   placeholder="user@example.com">
                        </div>
                    </div>

                    <!-- Telegram Fields -->
                    <div id="telegram_fields" class="hidden space-y-4">
                        <div>
                            <label for="bot_token" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Bot Token
                            </label>
                            <input type="text" 
                                   id="bot_token" 
                                   name="bot_token" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                                   placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Get from @BotFather on Telegram
                            </p>
                        </div>
                        <div>
                            <label for="chat_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Chat ID
                            </label>
                            <input type="text" 
                                   id="chat_id" 
                                   name="chat_id" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm" 
                                   placeholder="123456789">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Use @userinfobot to get your chat ID
                            </p>
                        </div>
                    </div>

                    <!-- Discord Fields -->
                    <div id="discord_fields" class="hidden space-y-4">
                        <div>
                            <label for="discord_webhook" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Webhook URL <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="discord_webhook" 
                                   name="discord_webhook_url" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm font-mono" 
                                   placeholder="https://discord.com/api/webhooks/1234567890/abcdefg..."
                                   autocomplete="off">
                            <p class="mt-1.5 text-xs text-gray-500">
                                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                Paste the complete webhook URL from Discord Server Settings → Integrations → Webhooks
                            </p>
                        </div>
                    </div>

                    <!-- Slack Fields -->
                    <div id="slack_fields" class="hidden space-y-4">
                        <div>
                            <label for="slack_webhook" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Webhook URL
                            </label>
                            <input type="text" 
                                   id="slack_webhook" 
                                   name="slack_webhook_url" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm font-mono" 
                                   placeholder="https://hooks.slack.com/services/..."
                                   autocomplete="off">
                            <p class="mt-1.5 text-xs text-gray-500">
                                Create in Slack App Settings → Incoming Webhooks
                            </p>
                        </div>
                    </div>

                    <button type="submit" 
                            class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors text-sm">
                        <i class="fas fa-plus mr-2"></i>
                        Add Channel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Assigned Domains -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-globe text-gray-400 mr-2 text-sm"></i>
                Assigned Domains (<?= count($group['domains']) ?>)
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($group['domains'])): ?>
                <div class="text-center py-10">
                    <i class="fas fa-globe text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">No domains assigned to this group yet</p>
                    <a href="/domains/create" class="mt-3 inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-plus mr-2"></i>
                        Add a Domain
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($group['domains'] as $domain): ?>
                        <a href="/domains/<?= $domain['id'] ?>" class="block bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md hover:border-primary transition-all duration-200">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-12 h-12 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-globe text-primary text-xl"></i>
                                </div>
                                <?php
                                $statusClass = $domain['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                                    <?= ucfirst($domain['status']) ?>
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-800 mb-2 truncate"><?= htmlspecialchars($domain['domain_name']) ?></h3>
                            <p class="text-sm text-gray-600 flex items-center">
                                <i class="far fa-calendar mr-2"></i>
                                Expires: <?= date('M j, Y', strtotime($domain['expiration_date'])) ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleChannelFields() {
    const channelType = document.getElementById('channel_type').value;
    
    // Get all input fields
    const emailField = document.getElementById('email');
    const botTokenField = document.getElementById('bot_token');
    const chatIdField = document.getElementById('chat_id');
    const discordWebhook = document.getElementById('discord_webhook');
    const slackWebhook = document.getElementById('slack_webhook');
    
    // Remove required from all
    emailField.removeAttribute('required');
    botTokenField.removeAttribute('required');
    chatIdField.removeAttribute('required');
    discordWebhook.removeAttribute('required');
    slackWebhook.removeAttribute('required');
    
    // Hide all fields
    document.getElementById('email_fields').classList.add('hidden');
    document.getElementById('telegram_fields').classList.add('hidden');
    document.getElementById('discord_fields').classList.add('hidden');
    document.getElementById('slack_fields').classList.add('hidden');
    
    // Show selected field and make required
    if (channelType) {
        document.getElementById(channelType + '_fields').classList.remove('hidden');
        
        // Set required based on type
        switch(channelType) {
            case 'email':
                emailField.setAttribute('required', 'required');
                break;
            case 'telegram':
                botTokenField.setAttribute('required', 'required');
                chatIdField.setAttribute('required', 'required');
                break;
            case 'discord':
                discordWebhook.setAttribute('required', 'required');
                discordWebhook.focus(); // Auto-focus for easy paste
                break;
            case 'slack':
                slackWebhook.setAttribute('required', 'required');
                slackWebhook.focus();
                break;
        }
    }
}

// Form validation before submit
const addChannelForm = document.querySelector('form[action="/channels/add"]');
if (addChannelForm) {
    addChannelForm.addEventListener('submit', function(e) {
    const channelType = document.getElementById('channel_type').value;
    
    if (!channelType) {
        e.preventDefault();
        alert('Please select a channel type');
        return false;
    }
    
    // Validate Discord webhook
    if (channelType === 'discord') {
        const webhookField = document.getElementById('discord_webhook');
        const webhookUrl = webhookField.value.trim();
        
        if (!webhookUrl) {
            e.preventDefault();
            alert('Please enter the Discord webhook URL');
            webhookField.focus();
            return false;
        }
        if (!webhookUrl.includes('discord.com/api/webhooks/')) {
            e.preventDefault();
            alert('Invalid Discord webhook URL. It should start with:\nhttps://discord.com/api/webhooks/');
            webhookField.focus();
            return false;
        }
    }
    
    // Validate Slack webhook
    if (channelType === 'slack') {
        const webhookUrl = document.getElementById('slack_webhook').value.trim();
        if (!webhookUrl) {
            e.preventDefault();
            alert('Please enter the Slack webhook URL');
            document.getElementById('slack_webhook').focus();
            return false;
        }
    }
    
    return true;
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>
