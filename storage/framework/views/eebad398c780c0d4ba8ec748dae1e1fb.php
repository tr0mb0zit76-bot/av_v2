<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Автопартнер</title>
    
    
    <style>
        :root {
            --primary-dark: #1a3b5e;
            --primary-soft: #2c4b6e;
            --accent-main: #c17b4b;
            --accent-light: #d89464;
            --bg-warm: #faf7f2;
            --bg-card: #ffffff;
            --text-dark: #1f2a36;
            --text-soft: #4a5562;
            --border-light: #e5ddd2;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-warm);
            color: var(--text-dark);
            height: 100vh;
            overflow: hidden;
        }
    </style>
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
</head>
<body>
    <div id="app"></div>
</body>
</html><?php /**PATH C:\OSPanel\home\avtopartner.local\resources\views/index.blade.php ENDPATH**/ ?>