<?php
if (!defined('ABSPATH')) { exit; }

function mhm_admin_analytics_page(): void {
    ?>
    <div class="wrap mhm-admin-wrap">
      <h1>Analytics</h1>
      <div class="mhm-admin-card"><canvas id="mhmAnalyticsChart" height="120"></canvas></div>
      <div class="mhm-admin-card"><canvas id="mhmUsageChart" height="120"></canvas></div>
    </div>
    <?php
}
