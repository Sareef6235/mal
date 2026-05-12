<?php
/**
 * Premium dashboard view.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mal-lms-shell" data-theme="dark">
	<aside class="mal-lms-sidebar" aria-label="<?php esc_attr_e( 'LMS navigation', 'mal-premium-lms' ); ?>">
		<div class="mal-lms-brand"><span class="mal-lms-logo">ML</span><strong><?php esc_html_e( 'MAL Premium LMS', 'mal-premium-lms' ); ?></strong></div>
		<nav>
			<a class="is-active" href="#overview"><span>🏠</span><?php esc_html_e( 'Overview', 'mal-premium-lms' ); ?></a>
			<a href="#plans"><span>🗓</span><?php esc_html_e( 'Monthly Plans', 'mal-premium-lms' ); ?></a>
			<a href="#receipts"><span>🧾</span><?php esc_html_e( 'Receipts', 'mal-premium-lms' ); ?></a>
			<a href="#courses"><span>🎬</span><?php esc_html_e( 'Courses', 'mal-premium-lms' ); ?></a>
			<a href="#exams"><span>🏆</span><?php esc_html_e( 'Exams', 'mal-premium-lms' ); ?></a>
		</nav>
	</aside>

	<main class="mal-lms-main">
		<header class="mal-lms-topbar">
			<button class="mal-lms-hamburger" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'mal-premium-lms' ); ?>">☰</button>
			<label class="mal-lms-search"><span>⌕</span><input type="search" data-live-filter placeholder="<?php esc_attr_e( 'Search plans, receipts, students...', 'mal-premium-lms' ); ?>" /></label>
			<div class="mal-lms-top-actions">
				<button class="mal-lms-icon-btn" type="button" aria-label="<?php esc_attr_e( 'Notifications', 'mal-premium-lms' ); ?>">🔔<b><?php echo esc_html( (string) $counts['receipts'] ); ?></b></button>
				<button class="mal-lms-theme-toggle" type="button" data-theme-toggle><?php esc_html_e( 'Theme', 'mal-premium-lms' ); ?></button>
				<div class="mal-lms-profile"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
			</div>
		</header>

		<section id="overview" class="mal-lms-section mal-lms-fade-in">
			<div class="mal-lms-hero">
				<div>
					<p class="mal-lms-eyebrow"><?php esc_html_e( 'Premium SaaS Dashboard', 'mal-premium-lms' ); ?></p>
					<h1><?php esc_html_e( 'Monthly plans, receipts, LMS, attendance, and exams in one secure workspace.', 'mal-premium-lms' ); ?></h1>
				</div>
				<button class="mal-lms-primary" data-open-modal="plan-modal"><?php esc_html_e( '+ Add Weekly Plan', 'mal-premium-lms' ); ?></button>
			</div>

			<div class="mal-lms-kpi-grid">
				<?php foreach ( array( 'courses' => __( 'Courses', 'mal-premium-lms' ), 'plans' => __( 'Plan Items', 'mal-premium-lms' ), 'receipts' => __( 'Pending Receipts', 'mal-premium-lms' ), 'attendees' => __( 'Attendance Marks', 'mal-premium-lms' ) ) as $key => $label ) : ?>
					<article class="mal-lms-card mal-lms-kpi">
						<span><?php echo esc_html( $label ); ?></span>
						<strong data-counter="<?php echo esc_attr( (string) $counts[ $key ] ); ?>">0</strong>
						<small><?php esc_html_e( 'Live system metric', 'mal-premium-lms' ); ?></small>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section id="plans" class="mal-lms-section">
			<div class="mal-lms-section-head">
				<h2><?php esc_html_e( 'Monthly Plan Management', 'mal-premium-lms' ); ?></h2>
				<div class="mal-lms-filter-row"><input data-filter-class placeholder="<?php esc_attr_e( 'Filter by class', 'mal-premium-lms' ); ?>" /><input data-filter-subject placeholder="<?php esc_attr_e( 'Filter by subject', 'mal-premium-lms' ); ?>" /></div>
			</div>
			<div class="mal-lms-plan-board" data-sortable-plans>
				<?php foreach ( $plans as $plan ) : ?>
					<article class="mal-lms-plan-card" data-plan-id="<?php echo esc_attr( (string) $plan->id ); ?>" data-search-text="<?php echo esc_attr( strtolower( $plan->title . ' ' . $plan->class_name . ' ' . $plan->subject ) ); ?>">
						<div><b><?php echo esc_html( $plan->title ); ?></b><span><?php echo esc_html( $plan->class_name . ' • ' . $plan->subject ); ?></span></div>
						<small><?php echo esc_html( sprintf( /* translators: %d: week number */ __( 'Week %d', 'mal-premium-lms' ), (int) $plan->week_number ) ); ?></small>
					</article>
				<?php endforeach; ?>
				<div class="mal-lms-skeleton" aria-hidden="true"></div>
			</div>
		</section>

		<section id="receipts" class="mal-lms-section">
			<div class="mal-lms-section-head"><h2><?php esc_html_e( 'Receipt Management', 'mal-premium-lms' ); ?></h2><button class="mal-lms-danger" data-bulk-delete><?php esc_html_e( 'Bulk Delete', 'mal-premium-lms' ); ?></button></div>
			<div class="mal-lms-table-wrap">
				<table class="mal-lms-table">
					<thead><tr><th><input type="checkbox" data-check-all /></th><th><?php esc_html_e( 'Student', 'mal-premium-lms' ); ?></th><th><?php esc_html_e( 'Amount', 'mal-premium-lms' ); ?></th><th><?php esc_html_e( 'Status', 'mal-premium-lms' ); ?></th><th><?php esc_html_e( 'Actions', 'mal-premium-lms' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $receipts as $receipt ) : ?>
							<tr data-search-text="<?php echo esc_attr( strtolower( $receipt->status . ' ' . $receipt->amount ) ); ?>">
								<td><input type="checkbox" value="<?php echo esc_attr( (string) $receipt->id ); ?>" data-receipt-check /></td>
								<td><?php echo esc_html( get_userdata( (int) $receipt->student_id )->display_name ?? __( 'Student', 'mal-premium-lms' ) ); ?></td>
								<td><?php echo esc_html( $receipt->currency . ' ' . number_format_i18n( (float) $receipt->amount, 2 ) ); ?></td>
								<td><span class="mal-lms-badge is-<?php echo esc_attr( $receipt->status ); ?>"><?php echo esc_html( ucfirst( $receipt->status ) ); ?></span></td>
								<td><button data-preview-receipt="<?php echo esc_attr( wp_json_encode( $receipt ) ); ?>"><?php esc_html_e( 'Preview', 'mal-premium-lms' ); ?></button><button data-receipt-status="approved" data-id="<?php echo esc_attr( (string) $receipt->id ); ?>">✓</button><button data-receipt-status="rejected" data-id="<?php echo esc_attr( (string) $receipt->id ); ?>">×</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section id="courses" class="mal-lms-section mal-lms-grid-two">
			<div class="mal-lms-card"><h2><?php esc_html_e( 'Course Management', 'mal-premium-lms' ); ?></h2><button class="mal-lms-primary" data-open-modal="course-modal"><?php esc_html_e( '+ Add Course', 'mal-premium-lms' ); ?></button></div>
			<div class="mal-lms-card"><h2><?php esc_html_e( 'Video + PDF Library', 'mal-premium-lms' ); ?></h2><p><?php esc_html_e( 'Use the modal to attach WordPress Media Library videos and PDFs securely.', 'mal-premium-lms' ); ?></p></div>
		</section>

		<section id="exams" class="mal-lms-section mal-lms-grid-two">
			<div class="mal-lms-card"><h2><?php esc_html_e( 'Attendance System', 'mal-premium-lms' ); ?></h2><p><?php esc_html_e( 'Attendance records are stored per student, course, date, status, and marker for reporting and mobile sync.', 'mal-premium-lms' ); ?></p></div>
			<div class="mal-lms-card"><h2><?php esc_html_e( 'Exam, Results & Certificates', 'mal-premium-lms' ); ?></h2><p><?php esc_html_e( 'Exam results and certificate records are normalized for REST dashboards. Frontend certificate views can be printed or exported as PDF by the browser.', 'mal-premium-lms' ); ?></p><button class="mal-lms-primary" type="button" data-open-modal="certificate-modal"><?php esc_html_e( 'Preview Certificate', 'mal-premium-lms' ); ?></button></div>
		</section>
	</main>
</div>

<?php include MAL_LMS_PATH . 'admin/views/modals.php'; ?>
