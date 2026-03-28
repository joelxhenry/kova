<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kova is a free tool for freelancers to create invoices, track expenses, and stay on top of who owes what.">
    <meta name="theme-color" content="#172726">
    <meta property="og:title" content="Kova | Free invoicing for freelancers">
    <meta property="og:description" content="Create professional invoices, track expenses, and get paid. No subscriptions, no limits.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/icons/icon-512.png">
    <title>Kova | Free Invoicing for Freelancers</title>

    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        .fade-in { opacity: 0; transform: translateY(20px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        .fade-in.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-background text-foreground font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-background/80 backdrop-blur-lg border-b border-border/50">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="/" class="text-xl font-bold text-foreground">Kova</a>

                <div class="hidden sm:flex items-center gap-6">
                    <a href="#features" class="text-sm text-muted-foreground hover:text-foreground transition-colors">Features</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/login" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">Log in</a>
                    <a href="/register" class="px-4 py-2 text-sm font-medium bg-foreground text-background rounded-full hover:bg-foreground/90 transition-colors">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative overflow-hidden">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-28 md:py-36">
            <div class="max-w-2xl">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold tracking-tight leading-[1.1]">
                    Invoicing<br>
                    <span class="text-accent">without the hassle.</span>
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-muted-foreground leading-relaxed max-w-lg">
                    Kova lets you create invoices, send them to clients, and keep track of your expenses. Built for freelancers. Completely free.
                </p>
                <div class="mt-8">
                    <a href="/register" class="px-6 py-3 text-sm font-medium bg-accent text-white rounded-full hover:bg-accent/90 transition-colors">
                        Create your first invoice
                    </a>
                </div>
            </div>
        </div>
        <div class="absolute top-20 right-0 w-96 h-96 bg-accent/5 rounded-full blur-3xl -z-10 hidden lg:block"></div>
    </section>

    <section id="features" class="py-20 md:py-32">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight">What you get</h2>
                <p class="mt-4 text-muted-foreground max-w-lg mx-auto">
                    The tools freelancers actually need, without the bloat.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 md:gap-8">
                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-3-3v6m-7 4h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Invoicing</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Build invoices with line items, custom numbering, and your business details. Send them as PDFs or by email.
                    </p>
                </div>

                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 8v2m-7-4h14"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Expenses</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Log what you spend, sort it by category, and see where the money goes each month.
                    </p>
                </div>

                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Clients</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Keep your client list organized with contacts, addresses, and a clear record of every invoice you've sent them.
                    </p>
                </div>

                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Overdue alerts</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        When an invoice passes its due date, Kova flags it and sends you a notification so nothing slips through.
                    </p>
                </div>

                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Clean PDFs</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Generate polished PDF invoices with your branding, payment details, and line item breakdowns. One click to download.
                    </p>
                </div>

                <div class="fade-in bg-card rounded-2xl shadow-sm p-6 md:p-8 border border-border/50">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Actually free</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        No trial that expires. No feature gates. No credit card. Use it as much as you want.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 md:py-28 bg-dark-surface text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 text-center fade-in">
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight">
                Stop chasing payments.
            </h2>
            <p class="mt-4 text-white/60 max-w-md mx-auto">
                Send a proper invoice, know when it's overdue, and keep your books clean. That's it.
            </p>
            <a href="/register" class="mt-8 inline-block px-8 py-3.5 text-sm font-medium bg-accent text-white rounded-full hover:bg-accent/90 transition-colors">
                Create your account
            </a>
        </div>
    </section>

    <footer class="py-12 border-t border-border">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-lg font-bold text-foreground">Kova</span>
                    <span class="text-sm text-muted-foreground ml-2">Invoicing for freelancers</span>
                </div>
                <div class="flex items-center gap-6 text-sm text-muted-foreground">
                    <a href="/login" class="hover:text-foreground transition-colors">Log in</a>
                    <a href="/register" class="hover:text-foreground transition-colors">Sign up</a>
                </div>
            </div>
            <div class="mt-6 text-center sm:text-left text-xs text-muted-foreground">
                &copy; {{ date('Y') }} Kova
            </div>
        </div>
    </footer>

    <script>
        document.documentElement.style.scrollBehavior = 'smooth';
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
        }, { threshold: 0.1 });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
    </script>
</body>
</html>
