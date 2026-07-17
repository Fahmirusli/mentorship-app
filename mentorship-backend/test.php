<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$user = App\Models\User::find(2);
$req = Illuminate\Http\Request::create('/api/wallet', 'GET');
$req->setUserResolver(fn() => $user);
$c = new App\Http\Controllers\Api\WalletController();
echo json_encode($c->index($req)->getData());
