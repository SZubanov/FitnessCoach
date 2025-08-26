<?php

return [
    'button' => [
        'cancel' => '❌ Cancel',
        'link_new' => '🔄 New Link code',
        'check_link' => '🔄 Check account',
        'measurements' => '📏 Body Measurements',
        'sync' => '🔄 Synchronization',
        'help' => '❓ Help',
    ],
    'messages' => [
        'welcome' => "👋 Welcome to FitnessCoach!\n\nThis bot will help you:\n• 📏 Record body measurements\n• 🔄 Sync data with FatSecret\n\nChoose an action:",
        'help' => "❓ *Command Help*\n\n/start - Start working with the bot\n/measurements - Add body measurements\n/sync - Sync with FatSecret\n/link - Link to existing account\n/help - Show this help\n\n📅 *Working with dates:*\n• Quick selection: Today, Yesterday, 2 days ago\n• Calendar for precise date\n• Date range for synchronization\n\n📏 *Body measurements:*\nSupported types: chest, waist, hips, bicep, thigh\n\n🔄 *Synchronization:*\n• Weight synchronization\n• Food synchronization\n• Selective synchronization by dates",
        'account_link' => [
            'new' => "🔗 *Link to Existing Account*\n\nTo link your Telegram to an existing account:\n\n1️⃣ Open the web application\n2️⃣ Sign in to your account\n3️⃣ Go to settings\n4️⃣ Enter the link code: `:code`\n\n🕒 Code is valid for 15 minutes\n\n📱 *Website address:* :url\n\n",
            'exist' => "🔗 *Account Link*\n\nYou already have a linked account:\n👤 :name\n📧 :email\n\nDo you want to link a different account?",
        ]
    ]
];
