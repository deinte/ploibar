<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PloiBar</title>
    @vite('resources/css/menubar.css')
    @livewireStyles
</head>
<body>
    <livewire:status-dashboard />
    @livewireScripts
    <script>
    function copyText(text, el) {
        navigator.clipboard.writeText(text).then(() => {
            el.classList.add('detail-copyable--copied');
            setTimeout(() => el.classList.remove('detail-copyable--copied'), 1200);
        });
    }
    </script>
</body>
</html>
