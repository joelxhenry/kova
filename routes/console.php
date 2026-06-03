<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('kova:check-overdue-invoices')->daily()->at('06:00');
Schedule::command('kova:process-recurring')->daily()->at('00:30');
