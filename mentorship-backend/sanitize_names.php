<?php
// Sanitize all user names to remove "Welcome Back, "
$users = App\Models\User::all();
foreach ($users as $user) {
    if (str_starts_with(strtolower($user->name), 'welcome back, ')) {
        $user->name = substr($user->name, 14);
        $user->save();
        echo "Sanitized user ID {$user->id} to: {$user->name}\n";
    } elseif (str_starts_with(strtolower($user->name), 'welcome back ')) {
        $user->name = substr($user->name, 13);
        $user->save();
        echo "Sanitized user ID {$user->id} to: {$user->name}\n";
    }
}
echo "Done sanitizing names.\n";
