<?php
$errorMessage = $error_message ?? '';
$showLockout = $show_lockout ?? false;
$oldInput = $old ?? [];
$usernameValue = $oldInput['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMC Login - Aircraft Movement Control</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="gradient-bg min-h-screen font-sans flex items-center justify-center p-4">
    <div class="w-full max-w-md mx-auto">
        <div class="container-bg rounded-xl p-6 lg:p-8 shadow-2xl">
            <h1 class="text-2xl lg:text-3xl font-bold text-center text-amc-dark-blue mb-2">AMC Login</h1>
            <p class="text-center text-gray-600 mb-6">Aircraft Movement Control System</p>

            <?php if ($errorMessage): ?>
                <div class="mb-4 p-3 rounded-md text-sm <?= $showLockout ? 'bg-yellow-50 text-yellow-800 border border-yellow-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">Username or Email:</label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($usernameValue) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password:</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-amc-blue focus:shadow-sm transition-all duration-300">
                </div>
                <button type="submit" name="login" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-2 px-4 rounded-md font-semibold text-sm transition-all duration-300 hover:-translate-y-1 shadow-md">Login</button>
            </form>

            <p class="mt-4 text-xs text-gray-600 text-center">
                Contact your administrator if you need access or have forgotten your password.
            </p>
        </div>
    </div>
</body>
</html>

