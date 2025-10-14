<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add user_id foreign key to telegraph_chats table
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('chat_id')->constrained('users')->nullOnDelete();
            $table->index('user_id');
        });

        // Step 2: Create TelegraphBot record with existing bot token
        $botToken = env('TELEGRAM_TOKEN');
        $botName = env('TELEGRAM_BOT_USERNAME', 'FitnessCoachBot');

        if (empty($botToken)) {
            echo "⚠️  TELEGRAM_TOKEN not found in .env - skipping bot creation\n";
            return;
        }

        $bot = TelegraphBot::create([
            'token' => $botToken,
            'name' => $botName,
        ]);

        echo "✓ Created TelegraphBot: {$bot->name} (ID: {$bot->id})\n";

        // Step 3: Migrate existing users with telegram_id to TelegraphChat records
        $users = User::whereNotNull('telegram_id')->get();

        if ($users->isEmpty()) {
            echo "ℹ️  No users with telegram_id found - skipping chat migration\n";
            return;
        }

        $migratedCount = 0;
        foreach ($users as $user) {
            // Check if chat already exists
            $existingChat = TelegraphChat::where('chat_id', $user->telegram_id)->first();

            if ($existingChat) {
                // Update existing chat with user_id
                $existingChat->update(['user_id' => $user->id]);
                $migratedCount++;
            } else {
                // Create new chat record using the bot relationship
                $chat = $bot->chats()->create([
                    'chat_id' => $user->telegram_id,
                    'name' => $user->telegram_username ?? $user->name ?? "User {$user->id}",
                ]);

                // Update with user_id
                $chat->update(['user_id' => $user->id]);
                $migratedCount++;
            }
        }

        echo "✓ Migrated {$migratedCount} users to TelegraphChat records\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove user_id column from telegraph_chats
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Delete all telegraph data (optional - be careful in production!)
        TelegraphChat::truncate();
        TelegraphBot::truncate();

        echo "✓ Rolled back Telegraph bot setup and user migration\n";
    }
};