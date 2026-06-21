<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
echo "[TEST 1: GAMIFICATION]\n";
echo "Testing Gamification for User: " . $user->email . "\n";
$controller = new App\Http\Controllers\Api\GamificationController();
$request = Illuminate\Http\Request::create('/api/gamification', 'GET');
$request->setUserResolver(function() use ($user) { return $user; });
$response = $controller->getGamificationData($request);
echo json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n\n";

echo "[TEST 2: WEBSOCKET PUSH NOTIFICATIONS]\n";
echo "Testing Notification Broadcast...\n";
$notification = App\Models\NotificationLog::notify($user->id, 'system', 'Test Feature', 'Testing the push notifications feature!', ['test' => true]);
echo "Notification dispatched! ID: " . $notification->id . "\n";
