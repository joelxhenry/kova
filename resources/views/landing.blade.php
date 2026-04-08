<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kova is a free tool to create invoices and track expenses.">
    <meta name="theme-color" content="#172726">
    <meta property="og:title" content="Kova | Invoices and expenses">
    <meta property="og:description" content="Create invoices, track expenses, manage clients. Free.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/icons/icon-512.png">
    <title>Kova | Invoices & Expenses</title>

    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        .fade-in { opacity: 0; transform: translateY(24px); transition: opacity 0.7s cubic-bezier(0.4, 0, 0.2, 1), transform 0.7s cubic-bezier(0.4, 0, 0.2, 1); }
        .fade-in.visible { opacity: 1; transform: translateY(0); }
        .fade-in-delay-1 { transition-delay: 0.1s; }
        .fade-in-delay-2 { transition-delay: 0.2s; }
    </style>
</head>
<body class="bg-background text-foreground font-sans antialiased">

    <nav class="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/40">
        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <div class="flex h-16 items-center justify-between">
                <a href="/" class="text-xl font-bold tracking-tight text-foreground">Kova</a>

                <div class="hidden sm:flex items-center gap-8">
                    <a href="#features" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">Features</a>
                    <a href="#how-it-works" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">How it works</a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="/login" class="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200">Log in</a>
                    <a href="/register" class="px-5 py-2.5 text-sm font-medium bg-accent/10 text-accent rounded-full hover:bg-accent/20 transition-all duration-200">
                        Sign up
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="relative overflow-hidden py-24 sm:py-32 md:py-40">
        <div class="absolute top-16 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-muted/30 rounded-full blur-3xl -z-10"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 bg-accent/5 rounded-full blur-3xl -z-10"></div>
        <div class="absolute top-32 left-0 w-48 h-48 bg-muted/20 rounded-full blur-2xl -z-10"></div>

        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16 text-center">
            <h1 class="fade-in text-5xl sm:text-6xl md:text-[3.75rem] font-bold tracking-tight leading-tight">
                Invoices and expenses,<br>
                <span class="text-accent">sorted.</span>
            </h1>

            <p class="fade-in fade-in-delay-1 mt-6 text-lg sm:text-xl text-muted-foreground leading-relaxed max-w-xl mx-auto">
                Create invoices, log expenses, and keep track of your clients. Free to use.
            </p>

            <div class="fade-in fade-in-delay-2 mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/register" class="px-8 py-3.5 text-sm font-medium bg-accent/10 text-accent rounded-full hover:bg-accent/20 transition-all duration-200">
                    Create an account
                </a>
                <a href="#features" class="px-8 py-3.5 text-sm font-medium text-foreground bg-white border border-border rounded-full hover:border-muted-foreground hover:bg-gray-50 transition-all duration-250">
                    See features
                </a>
            </div>

            <!-- Preview card -->
            <div class="fade-in mt-16 sm:mt-20 mx-auto max-w-2xl">
                <div class="bg-card rounded-3xl shadow-lg border border-border/50 p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-xs text-muted-foreground uppercase tracking-wide font-medium">Invoice #0042</p>
                            <p class="text-2xl font-bold mt-1">$3,200.00</p>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium bg-emerald-50 text-emerald-600 rounded-full">Paid</span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Website redesign</span>
                            <span class="font-medium">$2,400.00</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Brand consultation</span>
                            <span class="font-medium">$800.00</span>
                        </div>
                        <div class="h-px bg-border/60 my-2"></div>
                        <div class="flex justify-between text-sm font-semibold">
                            <span>Total</span>
                            <span>$3,200.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-24 md:py-32">
        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight">What's included</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="fade-in group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-3-3v6m-7 4h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Invoices</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Line items, custom numbering, your business details. Export to PDF or send by email.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-1 group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 8v2m-7-4h14"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Expenses</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Log what you spend and sort by category. See where the money goes each month.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-2 group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Clients</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Contacts, addresses, and a full history of every invoice you've sent them.
                    </p>
                </div>

                <div class="fade-in group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Overdue alerts</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Invoices past their due date get flagged and you get a notification.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-1 group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">PDF export</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Download invoices as PDFs with your branding and line item breakdowns.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-2 group bg-card rounded-2xl border border-border/50 p-6 md:p-8 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-5">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Free</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        No trial, no feature limits, no credit card required.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="py-24 md:py-32 bg-muted/20">
        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight">How it works</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                <div class="fade-in text-center">
                    <div class="w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mx-auto mb-5">
                        <span class="text-xl font-bold text-accent">1</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Sign up</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Create an account and add your business details.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-1 text-center">
                    <div class="w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mx-auto mb-5">
                        <span class="text-xl font-bold text-accent">2</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Add your clients</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Save client details once. They show up when you create new invoices.
                    </p>
                </div>

                <div class="fade-in fade-in-delay-2 text-center">
                    <div class="w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mx-auto mb-5">
                        <span class="text-xl font-bold text-accent">3</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Send invoices</h3>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Create an invoice, download the PDF, and send it. Kova tracks the status.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-24 md:py-32">
        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <div class="fade-in bg-dark-surface rounded-3xl p-10 sm:p-14 md:p-16 flex flex-col md:flex-row items-center gap-10 md:gap-16">
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-white">
                        Ready to get started?
                    </h2>
                    <p class="mt-4 text-white/60 max-w-md">
                        Create an account and send your first invoice in a few minutes.
                    </p>
                </div>
                <div class="shrink-0">
                    <a href="/register" class="inline-block px-8 py-3.5 text-sm font-medium bg-accent/10 text-accent rounded-full hover:bg-accent/20 transition-all duration-200">
                        Sign up
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-16 border-t border-border/40">
        <div class="mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 sm:gap-8">
                <div>
                    <span class="text-lg font-bold text-foreground">Kova</span>
                    <p class="mt-3 text-sm text-muted-foreground leading-relaxed max-w-xs">
                        Invoicing and expense tracking for freelancers.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-foreground mb-4">Product</p>
                    <div class="flex flex-col gap-3">
                        <a href="#features" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">Features</a>
                        <a href="#how-it-works" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">How it works</a>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-semibold text-foreground mb-4">Account</p>
                    <div class="flex flex-col gap-3">
                        <a href="/login" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">Log in</a>
                        <a href="/register" class="text-sm text-muted-foreground hover:text-foreground transition-colors duration-200">Sign up</a>
                    </div>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-border/40 text-xs text-muted-foreground">
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
