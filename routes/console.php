<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('kova:check-overdue-invoices')->daily()->at('06:00');
