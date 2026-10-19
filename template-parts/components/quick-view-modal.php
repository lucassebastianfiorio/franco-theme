<?php
/**
 * Quick View Modal Component
 *
 * @package FrancoTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="modal-backdrop" id="modal-backdrop">
	<div class="modal-card modal-content" id="quick-view-modal-card">
		<button class="modal-close" id="modal-close" aria-label="<?php esc_attr_e( 'Cerrar vista rápida', 'franco-theme' ); ?>"><i class="fa-solid fa-xmark"></i></button>
		<div class="modal-body" id="modal-body">
			<!-- Rendered dynamically via JS -->
		</div>
	</div>
</div>

