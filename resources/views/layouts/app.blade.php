<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Git Tracker - Learn by Stepping Through Commits</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="min-h-screen bg-gradient-to-br from-zinc-950 via-emerald-950 to-zinc-950 relative overflow-x-hidden">
<!-- Animated Background -->
<div class="absolute inset-0 z-0">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-900/20 via-transparent to-transparent"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_var(--tw-gradient-stops))] from-amber-900/20 via-transparent to-transparent"></div>
    <div class="absolute inset-0 bg-pattern opacity-30"></div>
</div>

<!-- Floating Orbs -->
<div class="absolute inset-0 overflow-hidden z-0 pointer-events-none">
    <div class="floating-orb absolute w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl -top-48 -left-48"></div>
    <div class="floating-orb absolute w-96 h-96 bg-amber-500/10 rounded-full blur-3xl -bottom-48 -right-48 animation-delay-2"></div>
    <div class="floating-orb absolute w-64 h-64 bg-teal-500/10 rounded-full blur-3xl top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 animation-delay-4"></div>
</div>

<main>
    {{ $slot }}
</main>

@livewireScripts
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    Livewire.hook('morph.added', ({el}) => {
        lucide.createIcons();
    });
</script>
</body>
</html>