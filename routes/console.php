<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('kova:send-quarterly-reminders')->daily()->at('08:00');
Schedule::command('kova:check-overdue-invoices')->daily()->at('06:00');
Schedule::command('kova:check-gct-threshold')->weekly()->mondays()->at('09:00');
