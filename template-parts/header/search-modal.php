<?php
/**
 * Search Modal Template Part
 *
 * @package FrancoTheme
 * @version 1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="search-modal-backdrop" id="search-modal-backdrop">
	<div class="search-modal">
		<div class="search-modal-titlebar">
			<h3 class="search-modal-title">
				<i class="fa-solid fa-magnifying-glass icon-accent"></i>
				<?php esc_html_e( 'Buscar Productos', 'franco-theme' ); ?>
			</h3>
			<button id="search-modal-close" class="search-modal-close-btn" aria-label="<?php esc_attr_e( 'Cerrar búsqueda', 'franco-theme' ); ?>"><i class="fa-solid fa-xmark"></i></button>
		</div>

		<div class="search-input-wrap">
			<input type="text" id="mobile-search-input" class="search-input search-modal-input" placeholder="<?php esc_attr_e( 'Escribe para buscar zapatillas, botines, remeras...', 'franco-theme' ); ?>">
			<i class="fa-solid fa-magnifying-glass search-modal-input-icon"></i>
		</div>

		<div id="mobile-search-results" class="mobile-search-results">
			<div class="search-empty-state">
				<i class="fa-solid fa-magnifying-glass search-empty-icon"></i>
				<?php esc_html_e( 'Escribe para buscar productos...', 'franco-theme' ); ?>
			</div>
		</div>
	</div>
</div>
